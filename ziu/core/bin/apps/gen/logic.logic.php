<?php

class Logic
{

    public function init()
    {
    }

    public function main()
    {
        try {
            $lib = lib('common');
            $params = $lib->args(func_get_args());
            $params = $lib->config($params);
            $schema = $lib->schema($params);
            $class  = str_replace(' ', '_', ucwords(str_replace('_', ' ', $params['table'])));
            $template = $lib->template('logic', array(
                '#table#' => $params['table'],
                '#Table#' => $class,
                '#table_id#' => $schema['table_id'],
                '#func_prefix#' => ZIU_USER_FUNC_PREFIX,
            ));
            echo($template);
        } catch (Exception $e) {
            echo($e->getMessage());
        }
    }

}

