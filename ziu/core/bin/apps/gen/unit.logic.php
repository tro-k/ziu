<?php

class Unit
{

    public function init()
    {
    }

    public function main($type)
    {
        try {
            $lib = lib('common');
            $params = $lib->args(func_get_args());
            $params = $lib->config($params);
            $schema = $lib->schema($params);
            $class  = str_replace(' ', '_', ucwords(str_replace('_', ' ', $params['table'])));
            $default = '';
            switch ($type) {
                case 'index' :
                    $name = 'unit/' . $type;
                    break;
                case 'default' :
                    $default = $this->get_default($schema['columns']);
                    $name = 'unit/' . $type;
                    break;
                default :
                    throw new Exception("Error: no unit [unit/$type].\n");
            }
            $template = $lib->template($name, array(
                '#table#' => $params['table'],
                '#Table#' => $class,
                '#table_id#' => $schema['table_id'],
                '#default#'  => trim($default),
            ));
            echo($template);
        } catch (Exception $e) {
            echo($e->getMessage());
        }
    }

    private function get_default($columns)
    {
        $res = '';
        $lib = lib('common');
        foreach ($columns as $col) {
            $field = $col['field'];
            if ($lib->skip_column($field)) { continue; }
            $res .= "        \$req['$field'] = \$r->post('$field');\n";
        }
        return $res;
    }

}

