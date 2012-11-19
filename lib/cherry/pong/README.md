Cherry Pong
===========

Transparent asynchronous client-server communication.

## Using Pong

### Server side

On the server side, you need one or more callback methods that are then bound
to commands (setHandler) or pseudo-timers (setInterval). The interval timer
uses microsleep to emulate an approximate millisecond precision timer.

    use Cherry\Pong\PongEndpoint;
    class UpdatesEndpoint extends PongEndpoint {
        function init() {
            // Every 5 seconds we want to check for new updates
            $this->setInterval(5000,array($this,'run'));
            $this->setHandler('reset',array($this,'reset'));
        }
        function run() {
            $uid = $this->userid;
            $rs = UpdatesModel::findAllBy([
                'userid'=>$uid, 
                'unread'=>1 
            ], [ 
                'sort'=>'date desc'
            ]);
            if (count($rs) > 0) {
                $this->push('updates',[ 
                    'items'=>count($rs), 
                    'data'=>$rs
                ]);
            }
        }
        function reset() {
            UpdatesModel::updateBy([
                'unread'=>0,
                'userid'=>$uid
            ], [
                'unread'=>1
            ]);
        }        
    }

And in your controller, router, or wherever is suitable you need to create
a channel for your communication.

    $cid = Pong::getInstance()->bind(new UpdatesEndpoint());

This channel id must be passed on to the view somehow, as they are used to

### Client side

Client side is even easier:

Just make sure that your script properly inserts the dynamic head data, like this:

    <?php app::document()->writeHead(); ?>

This ensures that the pong support scripts are loaded. Then you just need to listen
for pushed messages from the server, or call the methods you have bound directly.

    <script type="text/javascript">
    // Use the PongJs function to get the full name to the channel instance.
    var ch = <?=PongJs::getChannel($cid)?>;
    // This listener will listen for packets of the updates type.
    ch.listen('updates',function(updates){
        // Do something with the data here
        for (var n = 0; n < updates.data.length; n++) {
            var update = updates.data[n];
            // Implement foo.updatemanager yourself, with a method to query if
            // an update is new and worthy of a toast.
            if ( !foo.updatemanager.query(update.updateid) ) {
                foo.updatemanager.push(update);
            }
        }
    });
    // Call on this to reset the updates
    function resetupdates() {
        var ch = <?=$cid?>;
        // This will call UpdatesEndpoint->reset();
        ch.reset();
    }
    </script>
