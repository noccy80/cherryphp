<?php
// !status; might be renamed
// !stability; unstable

namespace Cherry\Mvc\View;

use Cherry\Mvc\View;
use Cherry\Base\Event;
use Cherry\Mvc\Html;
use App;

class TableView extends View {

    private
            $view = null,
            $content = null,
            $data = null,
            $options = [
                'header-columns' => 0,
                'header-rows' => 0,
                'table-class' => 'tableview',
                'footer-class' => 'tableview',
                'items-per-page' => null
            ];

    private function buildTable($data) {
        $id = uniqid('table');
        $tvpag =
<<<EOT
function table_setpage(tableid,page) {
    alert('Set page ' + page);
}
EOT;
        App::document()->addInlineScript($tvpag,'text/javascript','tableview-pagination');
        $hc = empty($this->options['header-columns'])?0:$this->options['header-columns'];
        $hr = empty($this->options['header-rows'])?0:$this->options['header-rows'];
        $rows = [];
        $ri = 0;
        $ipp = $this->options['items-per-page'];
        foreach($data as $row) {
            $ri++; $ci = 0;
            $cols = [];
            foreach($row as $col) {
                $ci++;
                if (($ci <= $hc) || ($ri <= $hr))
                    $cols[] = html::th($col);
                else
                    $cols[] = html::td($col);
            }
            $rows[] = html::tr(join($cols));
            if (($ipp) && ($ri > $ipp)) break;
        }
        $table = html::table(join($rows),[ 'class' => $this->options['table-class'], 'style'=>'width:100%;' ]);
        $page = 0;
        $numpages = floor((count($data)-1)/$ipp) + 1;
        $pagelinks = html::a(' &laquo; First ', [ 'href'=>'javascript:return false;' ]);
        $pagelinks.= html::a(' &lsaquo; Prev ', [ 'href'=>'javascript:return false;' ]);
        for ($n = 1; $n <= $numpages; $n++)
            $pagelinks.= html::a(' {page} ', [ 'href'=>'javascript:return false;' ], [ 'page'=>$n ]);
        $pagelinks.= html::a(' Next &rsaquo; ', [ 'href'=>'javascript:return false;' ]);
        $pagelinks.= html::a(' Last &raquo; ', [ 'href'=>'javascript:return false;' ]);
        $table.= html::div('Page {page} of {pages} ({items} items) {links}',
            [
                'class' => $this->options['footer-class']
            ],
            [
                'page' => $page + 1,
                'pages' => $numpages,
                'items' => count($data),
                'links' => $pagelinks
            ]
        );
        return html::div($table,['style'=>'width:99%']);
    }

    public function render($return=false) {
        $this->content = $this->buildTable($this->data);
        if ($return)
            return $this->content;
        else
            echo $this->content;
    }

    public function __construct($data = null,array $options = null) {
        parent::__construct();
        // Constructor
        $this->data = $data;
        $this->options = array_merge($this->options,$options);
    }

}
