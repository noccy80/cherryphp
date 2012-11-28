<?php

namespace CherryTree\Widgets;

use Cherry\Mvc\Html;
use Cherry\Mvc\Widget;

class ShoutBox extends Widget {

    private
        $db = null;

    public static
        $js = [];

    function init() {

        $this->setRefreshTimer(10);
        $this->bindAction('postmessage', __CLASS__, 'postMessageAction');

    }

    function render() {

        $messages = html::div(
            $messageelems,
            [
                'style' => 'height:'.$this->height.'px; overflow-y:scroll;'
            ]
        );
        $actions = html::div(
            html::input(null,[ 'type'=>'text', 'id'=>$this->id.'text' ])-
            html::input(null,[ 'type'=>'button', 'id'=>$this->id.'post' ])
        );
        $this->attachAction($this->id.'text', 'keypress', ShoutBox::$js['keypress']);
        return join($boxes);

    }

    public function postMessageAction(WidgetAction $action) {
        $msg = $action->data->msg;

    }

}

ShoutBox::$js['global'] = <<<EOF


EOF;
Shoutbox::$js['keypress'] = <<<EOF
if (event.keyCode == 13) ShoutBox.postmessage();
EOF;
