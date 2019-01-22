<?php

require_once __DIR__ . '/../vendor/autoload.php';

class shopPlugmeinPluginSmartyTrace implements Tracy\IBarPanel
{

    static $ptr;
    /**
     * Base64 icon for Tracy panel.
     * @var string
     * @link http://www.flaticon.com/free-icon/database_51319
     * @author Freepik.com
     * @license http://file000.flaticon.com/downloads/license/license.pdf
     */
    protected static $icon = 'data:image/svg+xml;utf8;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pgo8IS0tIEdlbmVyYXRvcjogQWRvYmUgSWxsdXN0cmF0b3IgMTguMC4wLCBTVkcgRXhwb3J0IFBsdWctSW4gLiBTVkcgVmVyc2lvbjogNi4wMCBCdWlsZCAwKSAgLS0+CjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+CjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgdmVyc2lvbj0iMS4xIiBpZD0iQ2FwYV8xIiB4PSIwcHgiIHk9IjBweCIgdmlld0JveD0iMCAwIDM0Ny44NzMgMzQ3Ljg3MyIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgMzQ3Ljg3MyAzNDcuODczOyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSIgd2lkdGg9IjE2cHgiIGhlaWdodD0iMTZweCI+CjxnPgoJPHJlY3QgeD0iNjQuMTQ4IiB5PSI3Ni42MDQiIHdpZHRoPSIyMTkuNTc2IiBoZWlnaHQ9IjM0LjI0OSIgZmlsbD0iIzAwMDAwMCIvPgoJPHJlY3QgeD0iNjQuMTQ4IiB5PSIxMzYuMzg5IiB3aWR0aD0iMTUwLjA0NiIgaGVpZ2h0PSI4MC4yMTUiIGZpbGw9IiMwMDAwMDAiLz4KCTxyZWN0IHg9IjI0Mi45MjciIHk9IjEzNi4zODkiIHdpZHRoPSI0MC43OTgiIGhlaWdodD0iODAuMjE1IiBmaWxsPSIjMDAwMDAwIi8+Cgk8cGF0aCBkPSJNMzM3Ljg3MywzMy45MzdIMTBjLTUuNTIzLDAtMTAsNC40NzctMTAsMTB2MjEwLjM5M2MwLDUuNTIzLDQuNDc3LDEwLDEwLDEwaDExNC45NzkgICBjLTQuNDA3LDIxLjQ3Ni0xNC42MDEsMzIuNTcyLTE0LjY3NCwzMi42NTFjLTIuNzkzLDIuODg1LTMuNTksNy4xNi0yLjAyMywxMC44NTdjMS41NjYsMy42OTcsNS4xOTEsNi4wOTksOS4yMDcsNi4wOTloMTEyLjg5NSAgIGM0LjAxNiwwLDcuNjQxLTIuNDAyLDkuMjA3LTYuMDk5YzEuNTY2LTMuNjk3LDAuNzctNy45NzMtMi4wMjMtMTAuODU3Yy0wLjExNy0wLjEyMi0xMC4yNzktMTEuMTc0LTE0LjY3Ni0zMi42NTFoMTE0Ljk4MSAgIGM1LjUyMywwLDEwLTQuNDc3LDEwLTEwVjQzLjkzN0MzNDcuODczLDM4LjQxNCwzNDMuMzk2LDMzLjkzNywzMzcuODczLDMzLjkzN3ogTTMyNy44NzMsMjQ0LjMyOWgtMTYuMzExdi0yLjgxNiAgIGMwLTUuNTIzLTQuNDc3LTEwLTEwLTEwcy0xMCw0LjQ3Ny0xMCwxMHYyLjgxNmgtMTAuNDM5di0yLjgxNmMwLTUuNTIzLTQuNDc3LTEwLTEwLTEwYy01LjUyMiwwLTEwLDQuNDc3LTEwLDEwdjIuODE2aC00OS43ODIgICBoLTc0LjgwOUgyMFY1My45MzdoMzA3Ljg3M1YyNDQuMzI5eiIgZmlsbD0iIzAwMDAwMCIvPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+CjxnPgo8L2c+Cjwvc3ZnPgo=';

    /**
     * Title
     * @var string
     */
    protected static $title = 'Smarty logger';

    /**
     * Title HTML attributes
     * @var string
     */
    protected static $title_attributes = 'style="font-size:1.6em"';

    /**
     * Time table cell HTML attributes
     * @var string
     */
    protected static $time_attributes = 'style="font-weight:bold;color:#333;font-family:Courier New;font-size:1.1em"';

    /**
     * Renders HTML code for custom tab.
     * @return string
     */
    function getTab()
    {
        $html = '<img src="'.self::$icon.'" alt="mysqli queries logger" /> ';

        $html .= $this->getTotalTime().'ms';
        return $html;
    }

    function getTotalTime()
    {
        return round(microtime(true) - wa()->getView()->smarty->start_time, 4);
    }

    /**
     * Renders HTML code for custom panel.
     * @return string
     */
    public function getPanel()
    {

        $view = wa()->getView();
        $html = $view->fetch('eval: {debug}');
        return $html;
    }
}