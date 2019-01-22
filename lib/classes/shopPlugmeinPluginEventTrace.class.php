<?php

require_once __DIR__ . '/../vendor/autoload.php';

class shopPlugmeinPluginEventTrace implements Tracy\IBarPanel
{
    /**
     * Base64 icon for Tracy panel.
     * @var string
     * @link http://www.flaticon.com/free-icon/database_51319
     * @author Freepik.com
     * @license http://file000.flaticon.com/downloads/license/license.pdf
     */
    protected static $icon = 'data:image/svg+xml;utf8;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pgo8IS0tIEdlbmVyYXRvcjogQWRvYmUgSWxsdXN0cmF0b3IgMTYuMC4wLCBTVkcgRXhwb3J0IFBsdWctSW4gLiBTVkcgVmVyc2lvbjogNi4wMCBCdWlsZCAwKSAgLS0+CjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+CjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgdmVyc2lvbj0iMS4xIiBpZD0iQ2FwYV8xIiB4PSIwcHgiIHk9IjBweCIgd2lkdGg9IjE2cHgiIGhlaWdodD0iMTZweCIgdmlld0JveD0iMCAwIDkzLjUgOTMuNSIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgOTMuNSA5My41OyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+CjxnPgoJPGc+CgkJPHBhdGggZD0iTTkzLjUsNDAuODk5YzAtMi40NTMtMS45OTUtNC40NDctNC40NDgtNC40NDdIODEuOThjLTAuNzQtMi41NDUtMS43NTYtNS4wMDEtMy4wMzUtNy4zMzFsNC45OTgtNSAgICBjMC44MjYtMC44MjcsMS4zMDMtMS45NzMsMS4zMDMtMy4xNDZjMC0xLjE5LTAuNDYyLTIuMzA2LTEuMzAzLTMuMTQ2TDc1LjY3LDkuNTU1Yy0xLjYxMy0xLjYxNS00LjY3My0xLjYxOC02LjI5LDBsLTUsNSAgICBjLTIuMzI3LTEuMjgtNC43ODYtMi4yOTYtNy4zMzItMy4wMzd2LTcuMDdDNTcuMDQ4LDEuOTk1LDU1LjA1MywwLDUyLjYwMiwwSDQwLjg5OWMtMi40NTMsMC00LjQ0NywxLjk5NS00LjQ0Nyw0LjQ0OHY3LjA3MSAgICBjLTIuNTQ2LDAuNzQxLTUuMDA1LDEuNzU3LTcuMzMzLDMuMDM3bC01LTVjLTEuNjgtMS42NzktNC42MDktMS42NzktNi4yODgsMEw5LjU1NSwxNy44M2MtMS43MzQsMS43MzQtMS43MzQsNC41NTUsMCw2LjI4OSAgICBsNC45OTksNWMtMS4yNzksMi4zMy0yLjI5NSw0Ljc4OC0zLjAzNiw3LjMzM2gtNy4wN0MxLjk5NSwzNi40NTIsMCwzOC40NDcsMCw0MC44OTlWNTIuNmMwLDIuNDUzLDEuOTk1LDQuNDQ3LDQuNDQ4LDQuNDQ3aDcuMDcxICAgIGMwLjc0LDIuNTQ1LDEuNzU3LDUuMDAzLDMuMDM2LDcuMzMybC00Ljk5OCw0Ljk5OWMtMC44MjcsMC44MjctMS4zMDMsMS45NzQtMS4zMDMsMy4xNDZjMCwxLjE4OSwwLjQ2MiwyLjMwNywxLjMwMiwzLjE0NiAgICBsOC4yNzQsOC4yNzNjMS42MTQsMS42MTUsNC42NzQsMS42MTksNi4yOSwwbDUtNWMyLjMyOCwxLjI3OSw0Ljc4NiwyLjI5Nyw3LjMzMywzLjAzN3Y3LjA3MWMwLDIuNDUzLDEuOTk1LDQuNDQ4LDQuNDQ3LDQuNDQ4ICAgIGgxMS43MDJjMi40NTMsMCw0LjQ0Ni0xLjk5NSw0LjQ0Ni00LjQ0OFY4MS45OGMyLjU0Ni0wLjc0LDUuMDA1LTEuNzU2LDcuMzMyLTMuMDM3bDUsNWMxLjY4MSwxLjY4LDQuNjA4LDEuNjgsNi4yODgsMCAgICBsOC4yNzUtOC4yNzNjMS43MzQtMS43MzQsMS43MzQtNC41NTUsMC02LjI4OWwtNC45OTgtNS4wMDFjMS4yNzktMi4zMjksMi4yOTUtNC43ODcsMy4wMzUtNy4zMzJoNy4wNzEgICAgYzIuNDUzLDAsNC40NDgtMS45OTUsNC40NDgtNC40NDZWNDAuODk5eiBNNjIuOTQ3LDQ2Ljc1YzAsOC45MzItNy4yNjYsMTYuMTk3LTE2LjE5NywxNi4xOTdjLTguOTMxLDAtMTYuMTk3LTcuMjY2LTE2LjE5Ny0xNi4xOTcgICAgYzAtOC45MzEsNy4yNjYtMTYuMTk3LDE2LjE5Ny0xNi4xOTdDNTUuNjgyLDMwLjU1Myw2Mi45NDcsMzcuODE5LDYyLjk0Nyw0Ni43NXoiIGZpbGw9IiMwMDAwMDAiLz4KCTwvZz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8L3N2Zz4K';

    /**
     * Title
     * @var string
     */
    protected static $title = 'Event logger';

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
        $events = shopPlugmeinPlugin::$eventTiming;
        $count = count($events);
        if ($count == 0) {
            $html .= 'no events!';
            return self::$title;
        } elseif ($count == 1) {
            $html .= '1 event';
        } else {
            $html .= $count . ' events';
        }
        $html .= ' / '.$this->getTotalTime().'ms';
        return $html;
    }

    function getTotalTime()
    {
        $time = 0;
        foreach (shopPlugmeinPlugin::$eventTiming as $timing) {
            foreach ($timing as $event => $data) {
                foreach ($data as $hook) {
                    $time += $hook['execution_time'];
                }
            }
        }

        return $time;
    }

    /**
     * Renders HTML code for custom panel.
     * @return string
     */
    public function getPanel()
    {

        $queries = shopPlugmeinPlugin::$eventTiming;
        $html = '<h1 '.self::$title_attributes.'>'.self::$title.'</h1>';
        $html .= '<div class="tracy-inner">';
        if (count($queries) > 0) {
            $html .= '<table style="width:400px;">';
            $html .= '<tr>';
            $html .= '<th>Time(ms)</td>';
            $html .= '<th>Event</td>';
            $html .= '<th>Class</td>';
            $html .= '<th>Plugin</td>';
            $html .= '</tr>';

            foreach ($queries as $query) {
                foreach ($query as $event => $data) {
                    foreach ($data as $hook) {
                        $html .= '<tr>';
                        $html .= '<td><span '.self::$time_attributes.'>'.round($hook['execution_time'], 4).'</span></td>';
                        $html .= '<td>'.$event.'</td>';
                        $html .= '<td>'.$hook['class'].'</td>';
                        $html .= '<td>'.$hook['plugin_id'].'</td>';

                        $html .= '</tr>';
                    }
                }
            }



            $html .= '</table>';
        } else {
            $html .= '<p style="font-size:1.2em;font-weigt:bold;padding:10px">No events were executed!</p>';
        }
        $html .= '</div>';

        return $html;
    }
}