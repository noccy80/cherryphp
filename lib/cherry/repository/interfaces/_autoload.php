<?php

namespace Cherry\Repository\Interfaces;

interface DataServer {

}
interface DataBrowser {
    function browserGetGroups();
    function browserGetGroup($groupid);
}
interface PanelTab {
    function panelGetTabIcon();
    function panelGetTabLabel();
    function panelGetTabContent();
}
