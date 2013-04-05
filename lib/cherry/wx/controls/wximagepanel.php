<?php

namespace Cherry\Wx\Controls;

class wxImagePanel extends \wxPanel {
    private $m_image;
    private $m_bitmap;
    public function loadFile($src) {
        $this->m_image = new wxImage();
        $this->m_image->loadFile($src);
        $this->m_bitmap = new wxBitmap($this->m_image, 24);
        $this->Connect( \wxEVT_PAINT, [ $this, 'OnPaint' ] );
        $this->Refresh();
    }
    public function OnPaint($event) {
        $dc = new wxPaintDC( $this );
        $dc->DrawBitmap( $this->m_bitmap, 0, 0  );
    }
}

