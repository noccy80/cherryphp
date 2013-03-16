<?php

namespace Cherry\Core;

abstract class ServiceInstance {

    use \Cherry\Traits\TUuid;
    use \Cherry\Traits\TDebug;
    use \Cherry\Core\TEventEmitter;

    // Control Queue messages
    const CQ_REGISTER = 0x01;   /// Register client [+uuid], answer on broadcast
    const CQ_UNREGISTER = 0x02; /// Unregister client [+uuid]
    const CQ_QUIT = 0x03;       /// Shut down the service
    const CQ_SUSPEND = 0x04;    /// Suspend the service (if suspendable)
    const CQ_UNSUSPEND = 0x05;  /// Unsuspend the service (if suspended)
    const CQ_HIBERNATE = 0x06;  /// Hibernate the service and kill it
    const CQ_RESTORE = 0x07;    /// Restore the service from hibernation

    /*
     * To write to client, send CQ_WRITE on CH_CLIENT_WRITE + $clientid
     * To read from client, poll CH_CLIENT_READ + $clientid
     * To tell the service to shutdown, send CQ_QUIT on CH_CONTROLLER or CH_CLIENT_WRITE + $clientid
     */
    const CH_CONTROLLER = 0x00; /// Messages to the service controller
    const CH_BROADCAST = 0x01;  /// Messages from the service controller, addressed.
    const CH_CLIENT_OUT = 0x10; /// Client write messages start here.
    const CH_CLIENT_IN = 0x40;  /// Client read messages here

    protected $role = self::ROLE_UNDEFINED;
    protected $spid = null;
    protected $gpid = null;
    protected $pidfile = null;
    protected $flags = null;
    protected $state = self::STA_STOPPED;
    protected $ipcid = null;
    protected $serviceid = null;
    protected $reload = false; // If the service should reload its config etc

    /** this class role has not been determined yet */
    const ROLE_UNDEFINED = null;
    /** this class is a service controller */
    const ROLE_CONTROLLER = 1;
    /** this class is the actual service */
    const ROLE_SERVICE = 2;

    /** restart the service on exiting servicemain or failing */
    const SVC_RESTART = 0x0001;
    /** re-initialize the service every time servicemain is about to be called */
    const SVC_VOLATILE = 0x0002;
    /** do not wait before restarting the service */
    const SVC_NO_DELAY = 0x0004;

    /** service is stopped */
    const STA_STOPPED = 0;
    /** service is starting */
    const STA_STARTING = 1;
    /** service is started */
    const STA_STARTED = 2;
    /** service is stopping */
    const STA_STOPPING = 3;

    public function __construct($pidfile=null) {
        if ($pidfile) {
            $this->setPidFile($pidfile);
        }
    }

    public function getPidFile() {
        return $this->pidfile;
    }

    public function setPidFile($pidfile) {
        $this->debug("Setting PidFile: {$pidfile}");
        $this->pidfile = $pidfile;
        if (file_exists($pidfile))
            $this->spid = (int)file_get_contents($pidfile);
        if ($this->testpid()) {
            $this->state = self::STA_STARTED;
        }
    }

    public function getState() {
        return $this->state;
    }

    public function getServiceId() {
        return $this->serviceid;
    }

    /**
     * Test the pid and unset $spid if the service is not around.
     */
    private function testpid() {
        if (!$this->spid)
            return false;
        if (file_exists("/proc/{$this->spid}"))
            return true;
        $this->spid = null;
        return false;
    }

    public function __destruct() {
        if ($this->role == self::ROLE_CONTROLLER) {
            $this->debug("Writing pid {$this->spid} to {$this->pidfile}");
            if ($this->spid) {
                if ($this->pidfile)
                    file_put_contents($this->pidfile,$this->spid);
            }
        } elseif ($this->role == self::ROLE_SERVICE) {
            // Service
            if ($this->gpid) {
                $status = null;
                pcntl_waitpid($this->gpid,$status,\WUNTRACED);
            }
        } else {
            // Observer
        }
    }

    /**
     * Main routine of the service
     */
    abstract public function servicemain();

    /**
     * This function is invoked by the start() method and is responsible for
     * making sure the service mainloop is properly invoked. It will also
     * break when the service is asked to stop.
     */
    protected function runservice() {
        $this->state = self::STA_STARTED;
        // We break this loop manually
        $flags = $this->getFlags();
        while(true) {
            $this->servicemain();
            pcntl_signal_dispatch();
            if ($this->state == self::STA_STOPPING) break;
            if (!($flags & self::SVC_RESTART)) break;
            if (!($flags & self::SVC_NO_DELAY)) sleep(1);
        }
        $this->state = self::STA_STOPPED;
    }

    /**
     * Set up the signal handlers for the child
     */
    protected function bindhandlers() {
        pcntl_signal(\SIGQUIT, [ $this, "onSignalHandler" ]);
        pcntl_signal(\SIGTERM, [ $this, "onSignalHandler" ]);
        pcntl_signal(\SIGHUP, [ $this, "onSignalHandler" ]);
    }

    /**
     * this won't work. need a ServiceManager class for that
     */
    public final function onSignalHandler($signal) {
        if ($this->gpid)
            posix_kill($this->gpid, $signal);
        if ($signal == \SIGINT) {
            $this->debug("Received SIGINT, shutting down.");
        } elseif ($signal == \SIGQUIT) {
            $this->debug("Received SIGQUIT. Stopping service");
            $this->state = self::STA_STOPPING;
            if (is_callable([$this,"servicehalt"]))
                $this->servicehalt();
            if (is_callable([$this,"onShutdown"])) {
                \App::app()->warn("Warning: Service is using onShutdown which is deprecated. It should use servicehalt.");
                $this->onShutdown();
            }
        } elseif ($signal == \SIGTERM) {
            exit;
        } elseif ($signal == \SIGHUP) {
            $this->debug("Received SIGHUP, flagging for reload...");
            $this->reload = true;
        }
    }


    /**
     * Get the IpcChannel for the serviced
     */
    public function getIpcChannel() {
        return $this->channel;
    }

    public function start() {
        // Do we have a thread? If not, create one.
        if ($this->spid) {
            $this->debug("Service is already running");
            return false;
        }
        $this->debug("Starting service...");
        $this->emit("service.starting",$this->serviceid);
        $pid = pcntl_fork();
        if ($pid === false) {
            throw new \Exception("Failed to start the service: fork failed");
        } elseif ($pid == 0) {
            // For the child
            $this->role = self::ROLE_SERVICE;
            $this->bindhandlers();
            $this->runservice();
            exit(0);
        } else {
            // For the instance controller
            $this->role = self::ROLE_CONTROLLER;
            $this->debug("Service process is running with pid {$pid}");
            $this->spid = $pid;
            pcntl_signal(\SIGINT, [$this,"onSignalHandler"]);
            return true;
        }
    }

    public function getServicePid() {
        return $this->spid;
    }

    public function setServicePid($pid) {
        $this->spid = ((int)$pid)?:NULL;
    }

    public function isRunning() {
        // check the pid to see if the process is alive
        //$running = PosixUtils::checkPid($this->spid);
        return ($this->testpid());
    }

    public function stop() {
        // Do we have a thread? If so, send it a kill message
        $status = null;
        if ($this->spid) {
            $this->emit("service.stopping",$this->serviceid);
            pcntl_signal_dispatch();
            if (pcntl_waitpid($this->spid, $status, \WUNTRACED | \WNOHANG)) {
                if (!$this->testpid()) $this->debug("Service process has quit (%d)", pcntl_wexitstatus($status));
            }
            $this->debug("Killing service process {$this->spid}");
            for($n = 0; $n < 3; $n++) {
                // Send the process the kill signal
                posix_kill($this->spid, \SIGQUIT);
                pcntl_signal_dispatch();
                if (pcntl_waitpid($this->spid, $status, \WUNTRACED | \WNOHANG)) {
                    if (!$this->testpid()) {
                        $this->debug("Service process exited after SIGQUIT (%d)", pcntl_wexitstatus($status));
                        $this->spid = null;
                        return true;
                    }
                }
                $this->debug("Service process still running, waiting 5 seconds (try %d of %d)", $n+1,3);
                // Delay for ~5s
                for($s = 0; $s < 50; $s++) {
                    usleep(100000);
                }
            }
            posix_kill($this->spid, \SIGTERM);
            pcntl_signal_dispatch();
            if (pcntl_waitpid($this->spid, $status, \WUNTRACED)) {
                if (!$this->testpid()) {
                    $this->debug("Service process exited after SIGTERM (%d)", pcntl_wexitstatus($status));
                    $this->spid = null;
                    return true;
                }
                $this->debug("Warning: Service is not responding...");
                return false;
            } else {
                $this->debug("The service process could not be stopped");
                return false;
            }
        }
        return null;
    }

    public function reload() {
        // Do we have a thread? If so, send it a sighup
        $status = null;
        if ($this->spid) {
            $this->debug("Sending SIGHUP to service process");
            $this->emit("service.reloading",$this->serviceid);
            pcntl_signal_dispatch();
            posix_kill($this->spid, \SIGHUP);
            pcntl_signal_dispatch();
            return true;
        }
        return null;
    }

}
