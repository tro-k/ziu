<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * Paginate Library Engine.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

class Paginate
{

    // {{{ variable
    /**
     * Variables
     */
    private $config  = array(
                            // uri
                            'base_uri'   => '',
                            'uri_param'  => '',
                            'except_param' => '',
                            'num_format' => '/%d',
                            // page
                            'page_num'   => 1,
                            'total_page' => 1,
                            'num_link'   => 3,
                            // record
                            'limit_row'  => 10,
                            'total_row'  => 0,
                            'offset_row' => 0,
                            // text
                            'prev_text'  => '&lt;&lt; Prev',
                            'next_text'  => 'Next &gt;&gt;',
                            'first_text' => '[first]',
                            'last_text'  => '[last]',
                            'layout'     => '<div class="paginate"><span class="first">:first</span><span class="prev">:prev</span><span class="pages">:pages</span><span class="next">:next</span><span class="last">:last</span></div>',
                        );
    private $pages = array('start' => NULL, 'end' => NULL);
    // }}}

    /**
     * Constructor
     * @param array $config : list of config
     */
    public function __construct($config = NULL)
    {
        $this->config($config);
    }

    // {{{ config
    /**
     * Config
     * @param array $config : list of config
     * @return object this
     */
    public function config($config)
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }
        return $this;
    }
    // }}}

    // {{{ execute 
    /**
     * Execute
     * @return object this
     */
    public function execute()
    {
        // base uri
        if (empty($this->config['base_uri'])) {
            $this->config['base_uri'] = $this->base_uri();
        }
        // amount total page
        $this->config['total_page'] = ceil($this->config['total_row'] / $this->config['limit_row']);
        $this->config['total_page'] = $this->config['total_page'] ? $this->config['total_page'] : 1;
        // adjust page num
        if ($this->config['page_num'] > $this->config['total_page']) {
            $this->config['page_num'] = $this->config['total_page'];
        } elseif ($this->config['page_num'] < 1) {
            $this->config['page_num'] = 1;
        }
        // set offset row
        $this->offset();
        return $this;
    }
    // }}}

    // {{{ limit, offset
    /**
     * Get limit
     * @return integer limit row number
     */
    public function limit()
    {
        return $this->config['limit_row'];
    }

    /**
     * Get offset
     * @return integer offset row number
     */
    public function offset()
    {
        // set offset row
        $this->config['offset_row'] = ($this->config['page_num'] - 1) * $this->config['limit_row'];
        return $this->config['offset_row'];
    }
    // }}}

    // {{{ links, first, last, pages, prev, next
    /**
     * Get links
     * @param $html boolean : html flag
     * @return string all links
     */
    public function links($html = TRUE)
    {
        $link = '';
        $data = array();
        if ($this->config['total_page'] == 1) {
            return $html ? $link : $data;
        }
        $keys = array(':prev', ':pages', ':next',':first', ':last', ':total_row', ':current_page', ':total_page', ':offset_row', ':limit_row');
        $reps = array($this->prev($html), $this->pages($html), $this->next($html)
                     , $this->first($html),  $this->last($html)
                     , $this->config['total_row'], $this->config['page_num'], $this->config['total_page']
                     , $this->config['offset_row'], $this->config['limit_row']);
        if ($html) {
            $link = str_replace($keys, $reps, $this->config['layout']);
        } else {
            foreach ($keys as $key => $label) {
                $data[$label] = $reps[$key];
            }
        }
        return $html ? $link : $data;
    }

    /**
     * Get first link
     * @param $html boolean : html flag
     * @return string first link
     */
    public function first($html = TRUE)
    {
        $link = '';
        $data = array();
        if (is_null($this->pages['start'])) {
            return $html ? $link : $data;
        }
        if ($this->pages['start'] != 1) {
            $url = $this->uri_param($this->config['base_uri'] . $this->num_format(1));
            $link = '<a href="' . $url . '">' . $this->config['first_text'] . '</a>';
            $data = array('url' => $url);
        }
        return $html ? $link : $data;
    }

    /**
     * Get last link
     * @param $html boolean : html flag
     * @return string last link
     */
    public function last($html = TRUE)
    {
        $link = '';
        $data = array();
        if (is_null($this->pages['end'])) {
            return $html ? $link : $data;
        }
        if ($this->pages['end'] != $this->config['total_page']) {
            $url = $this->uri_param($this->config['base_uri'] . $this->num_format($this->config['total_page']));
            $link = '<a href="' . $url . '">' . $this->config['last_text'] . '</a>';
            $data = array('url' => $url);
        }
        return $html ? $link : $data;
    }

    /**
     * Get pages link
     * @param $html boolean : html flag
     * @return string pages link
     */
    public function pages($html = TRUE)
    {
        $data = array();
        $link = '';
        if ($this->config['total_page'] == 1) {
            return $html ? $link : $data;
        }
        $s_pos = $this->config['page_num'] - $this->config['num_link'];
        $e_pos = $this->config['page_num'] + $this->config['num_link'];
        $full_link = $this->config['num_link'] * 2;
        $start = ($s_pos > 0) ? $s_pos : 1;
        $end   = ($e_pos < $this->config['total_page']) ? $e_pos : $this->config['total_page'];
        $gap   = $end - $start;
        if ($end < $this->config['total_page'] && $gap < $full_link) {
            $adj = $full_link - $gap + $end;
            $end = $this->config['total_page'] < $adj ? $this->config['total_page'] : $adj;
        }
        if ($gap < $full_link) {
            $adj = $start - ($full_link - $gap);
            $start = $adj < 1 ? 1 : $adj;
        }
        for ($i = $start; $i <= $end; $i++) {
            $num = ($i == 1) ? '' : $i;
            $url = $this->uri_param($this->config['base_uri'] . $this->num_format($num));
            if ($this->config['page_num'] == $i) {
                $link .= '<span class="active">' . $i . '</span>';
                $data[] = array('page_id' => $i, 'active' => TRUE, 'url' => $url);
            } else {
                $link .= '<a href="' . $url . '">' . $i . '</a>';
                $data[] = array('page_id' => $i, 'active' => FALSE, 'url' => $url);
            }
        }
        $this->pages['start'] = $start;
        $this->pages['end']   = $end;
        return $html ? $link : $data;
    }

    /**
     * Get next link
     * @param $html boolean : html flag
     * @return string next link
     */
    public function next($html = TRUE)
    {
        $link = '';
        $data = array();
        if ($this->config['total_page'] == 1) {
            return $html ? $link : $data;
        }
        if ($this->config['page_num'] == $this->config['total_page']) {
            $link = $this->config['next_text'];
        } else {
            $next = $this->config['page_num'] + 1;
            $url  = $this->uri_param($this->config['base_uri'] . $this->num_format($next));
            $link = '<a rel="next" href="' . $url . '">' . $this->config['next_text'] . '</a>';
            $data = array('url' => $url);
        }
        return $html ? $link : $data;
    }

    /**
     * Get prev link
     * @param $html boolean : html flag
     * @return string prev link
     */
    public function prev($html = TRUE)
    {
        $link = '';
        $data = array();
        if ($this->config['total_page'] == 1) {
            return $html ? $link : $data;
        }
        if ($this->config['page_num'] == 1) {
            $link = $this->config['prev_text'];
        } else {
            $prev = $this->config['page_num'] - 1;
            $prev = ($prev == 1) ? '' : $prev;
            $url  = $this->uri_param($this->config['base_uri'] . $this->num_format($prev));
            $link = '<a rel="prev" href="' . $url . '">' . $this->config['prev_text'] . '</a>';
            $data = array('url' => $url);
        }
        return $html ? $link : $data;
    }

    /**
     * Number format
     * @param integer $num : page number
     * @return string page uri
     */
    private function num_format($num)
    {
        $format = '';
        if (! empty($num)) {
            $format = '/' . ltrim(sprintf($this->config['num_format'], $num), '/');
        }
        return $format;
    }

    /**
     * Base uri
     * @return string base uri
     */
    private function base_uri()
    {
        $uri = rtrim($_SERVER['REQUEST_URI'], '/');
        if (strpos($uri, '?') !== FALSE) {
            list($uri, $param) = explode('?', $uri);
            $uri = rtrim($uri, '/');
            if (empty($this->config['uri_param'])) {
                $this->config['uri_param'] = $param;
            }
        }
        $regex = preg_quote($this->num_format($this->config['page_num']));
        $uri = preg_replace('#' . $regex . '$#', '', $uri);
        return $uri;
    }

    /**
     * Uri param
     * @return string uri with param
     */
    private function uri_param($uri)
    {
        if (! empty($this->config['uri_param'])) {
            if (! empty($this->config['except_param'])) {
                $excepts = explode(',', $this->config['except_param']);
                $params  = explode('&', $this->config['uri_param']);
                foreach ($params as $i => $param) {
                    list($key, $val) = explode('=', $param);
                    if (in_array($key, $excepts)) {
                        unset($params[$i]);
                    }
                }
                $this->config['uri_param'] = implode('&', $params);
            }
            $uri = $uri . '?' . $this->config['uri_param'];
        }
        return $uri;
    }
    // }}}

}

