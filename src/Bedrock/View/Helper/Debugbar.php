<?php

namespace Peak\Bedrock\View\Helper;

use Peak\Bedrock\Application;
use Peak\Bedrock\Application\Kernel;
use Peak\Bedrock\View\Helper\Debug;

/**
 * Graphic version of Peak_View_Helper_debug
 * @uses    jQuery, Fugue icons, Peak\Bedrock\View\Helper\Debug, Peak\Common\Chrono
 */
class Debugbar extends Debug
{

    private $_console_log = [];

    /**
     * Display a bottom bar in your page
     */
    public function show($start_minimized = false, $theme = 'default')
    {
        //skip if env is not dev
        if (APPLICATION_ENV !== 'dev') {
            return;
        }
        if (!$this->view->engine() instanceof \Peak\Bedrock\View\Render\Layouts) {
            return;
        }

        //skip if view rendering is disabled
        if ($this->view->canRender() === false) {
            return;
        }

        //skip if view engine is Json
        if (strtolower($this->view->getEngineName()) === 'Json') {
            return;
        }
        
        //files included
        $files = $this->getFiles();
        //print_r($files);
        $files_count = count($files['app']) + count($files['peak']);

        //php 5.4, use $_SERVER['REQUEST_TIME_FLOAT']
        if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
            $chrono = (round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4) * 1000);
        } elseif (version_compare(PHP_VERSION, '5.1.0') >= 0) {
            $chrono = (round(microtime(true) - $_SERVER['REQUEST_TIME'], 4) * 1000);
        } else {
            $chrono = 'n/a';
        }

        //save chronos into session if exists
        $sid = session_id();
        if (!empty($sid)) {
            if (!isset($_SESSION['pkdebugbar']['chrono'])) {
                $_SESSION['pkdebugbar']['chrono'] = '{}';
            }
            $chronos = json_decode($_SESSION['pkdebugbar']['chrono'], true);
            $chronos[] = $chrono;
            $_SESSION['pkdebugbar']['chrono'] = json_encode($chronos);

            if (!isset($_SESSION['pkdebugbar']['pages_chrono'][$_SERVER['REQUEST_URI']])) {
                $_SESSION['pkdebugbar']['pages_chrono'][$_SERVER['REQUEST_URI']] = '{}';
            }
            $chronos = json_decode($_SESSION['pkdebugbar']['pages_chrono'][$_SERVER['REQUEST_URI']], true);
            $chronos[] = $chrono;
            $_SESSION['pkdebugbar']['pages_chrono'][$_SERVER['REQUEST_URI']] = json_encode($chronos);
        }

        //print css & js
        echo $this->getAssets();

        $theme = ($theme === 'dark') ? 'th-dark' : '';

        //debug bar html
        echo '<div id="pkdebugbar" class="'.$theme.'">
              <div class="pkdbpanel">
               <ul>
                <li><a class="thebar">&nbsp;&nbsp;&nbsp;Peak v'.Kernel::VERSION.'/PHP '.phpversion().'</a></li>
                <li><a class="clock pkdb_tab" id="pkdb_chrono" onclick="pkdebugShow(\'pkdb_chrono\');">'.$chrono.' ms</a></li>';

        echo '  <li><a class="memory">'.$this->getMemoryUsage().'</a></li>
                <li><a class="files pkdb_tab" id="pkdb_include" onclick="pkdebugShow(\'pkdb_include\');">'.$files_count.' Files</a></li>
                <li><a class="variables pkdb_tab" id="pkdb_vars" onclick="pkdebugShow(\'pkdb_vars\');">Variables</a></li>
                <li><a class="registry pkdb_tab" id="pkdb_registry" onclick="pkdebugShow(\'pkdb_registry\');">App Container</a></li>';

        if (!empty($this->_console_log)) {
            echo '<li><a class="console pkdb_tab" id="pkdb_consolelog" onclick="pkdebugShow(\'pkdb_consolelog\');">Console</a></li>';
        }
        echo '  <li id="togglebar"><a id="hideshow" class="hidebar" title="show/hide" onclick="pkdebugToggle();">&nbsp;</a></li>
               </ul>';
 

        //chrono
        echo '<div class="window medium" id="pkdb_chrono_window">';
        echo '<h2>Chrono</h2> Current: '.$chrono.' ms<br /><br />';
        if (isset($_SESSION['pkdebugbar']['chrono'])) {
            $chronos = json_decode($_SESSION['pkdebugbar']['chrono'], true);
            $nb_chrono = count($chronos);
            $sum_chrono = array_sum($chronos);
            $average_chrono = $sum_chrono / $nb_chrono;
            sort($chronos);
            $short = $chronos[0];
            rsort($chronos);
            $long = $chronos[0];
            echo 'Number of requests: '.$nb_chrono.'<br />';
            echo 'Average: '.round($average_chrono,2).'ms / request<br /><br />';
            echo 'Fastest request: '.$short.'ms<br />';
            echo 'Longest request: '.$long.'ms<br /><br />';
            
            echo 'Request(s) stats:<br /><div class="pre"><table><thead><tr><th>URI</th><th style="width:1px;">Average</th><th style="width:1px;">Count</th></tr></thead>';
            foreach ($_SESSION['pkdebugbar']['pages_chrono'] as $page => $chronos) {
                $chronos = json_decode($chronos, true);
                $count = count($chronos);
                if (!in_array(Application::conf('path.public'), array('','/'))) {
                    $page = str_replace(Application::conf('path.public'), '', $page);
                }
                $page = str_replace('//', '/', $page);
                echo '<tr><td>'.$page.'</td><td>'.round(array_sum($chronos) / $count,2).'ms</td><td>'.$count.'</td></tr>';
            }
            echo '</table></div><script></script>';
        } elseif ($chrono === 'n/a') {
            echo 'To get chrono, you must use Peak_Chrono::start() in your app launcher.<br />
                  To gather stats about requests, you need a session';
        }
        echo '</div>';

        //files included
        echo '<div class="window resizable" id="pkdb_include_window">';
        echo '<h2>Files information</h2>
              <strong>'.$files_count.' Files included<br />Total size: '.round($files['total_size'] / 1024, 2).' Kbs</strong><br />';
        echo '<h2>'.count($files['app']).' Application files:</h2>';
        foreach ($files['app'] as $appfile) {
            $size = round(filesize($appfile) / 1024, 2);
            $appfile = str_replace(basename($appfile),'<strong>'.basename($appfile).'</strong>', $appfile);
            echo $appfile.' - <small>'.$size.' Kbs</small><br />';
        }
        echo '<h2>'.count($files['peak']).' Library files:</h2>';
        foreach ($files['peak'] as $libfile) {
            $size = round(filesize($libfile) / 1024, 2);
            $libfile = str_replace(basename($libfile), '<strong>'.basename($libfile).'</strong>', $libfile);
            echo str_replace(LIBRARY_ABSPATH, '', $libfile).' - <small>'.$size.' Kbs</small><br />';
        }
        echo '</div>';

        //variables
        echo '<div class="window resizable" id="pkdb_vars_window">';
        $views_vars = htmlentities(print_r($this->view->getVars(), true));
        echo '<h2>VIEW</h2><pre>'.$views_vars.'</pre>';
        if (!empty($_SESSION)) {
            $sessions_vars = htmlentities(print_r($_SESSION, true));
            echo '<h2>$_SESSION</h2><pre>'.$sessions_vars.'</pre>';
        }
        echo '<h2>$_COOKIE</h2><pre>'.print_r($_COOKIE, true).'</pre>';
        echo '<h2>$_SERVER</h2><pre>'.print_r($_SERVER, true).'</pre>';
        echo '</div>';

        //app container
        echo '<div class="window resizable" id="pkdb_registry_window">';
        echo '<h2>'.count(Application::container()->getInstances()).' registered objects</h2>';
        foreach (Application::container()->getInstances() as $name => $data) {
            echo '<strong><a href="#'.$name.'">'.$name.'</a></strong><br />';
        }

        foreach (Application::container()->getInstances() as $name => $data) {
            $object_data = htmlentities(print_r($data,true));
            echo '<h2 id="'.$name.'">'.$name.'</h2><pre>'.$object_data.'</pre>';
        }
        
        echo '</div>';

        //console log (see method log())
        if (!empty($this->_console_log)) {
            echo '<div class="window resizable" id="pkdb_consolelog_window">';
            echo '<h2>Console log</h2>';

            foreach ($this->_console_log as $i => $item) {

                if (isset($item['title'])) {
                    echo '<strong>'.$item['title'].'</strong><br />';
                }
                if (is_array($item['data']) || is_object($item['data'])) {
                    echo '<pre>'.htmlentities(print_r($item['data'], true)).'</pre>';
                } else {
                    echo '<pre>'.htmlentities($item['data']).'</pre>';
                }
                echo '<br />';
            }
            echo '</div>';
        }

        echo '<script>pkdebugbar_start_minimized = '.($start_minimized ? 'true' : 'false').';</script>';


        echo '</div><!-- /pkdbpanel --></div><!-- /pkdebugbar -->';
    }

    /**
     * Add misc data to log in the debugbar
     *
     * @return $this
     */
    public function log($data, $title = null)
    {
        $this->_console_log[] = ['data' => $data, 'title' => $title];
        return $this;
    }

    /**
     * Get CSS & JS for the bar
     *
     * @return string
     */
    private function getAssets()
    {
        return '<style type="text/css">
                <!--
                '.(file_get_contents(dirname(__FILE__).'/debugbar/debugbar.css')).
                '-->
                </style>
                <script type="text/javascript">'.(file_get_contents(dirname(__FILE__).'/debugbar/debugbar.js')).'</script>
                <!--[if lt IE 9]>
                <style>
                    #pkdebugbar .clock, #pkdebugbar .memory, #pkdebugbar .files, #pkdebugbar .variables, #pkdebugbar .registry, #pkdebugbar .db,
                    #pkdebugbar .hidebar, #pkdebugbar .showbar, #pkdebugbar .console, #pkdebugbar .thebar
                    { background-image: none !important; }
                </style>
                <![endif]-->';
    }
}
