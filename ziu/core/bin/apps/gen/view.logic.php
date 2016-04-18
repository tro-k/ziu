<?php

class View
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
            $class  = str_replace(' ', '', ucwords(str_replace('_', ' ', $params['table'])));
            $index = array('th' => '', 'td' => '');
            $input = '';
            $confirm = '';
            switch ($type) {
                case 'index' :
                    $index = $this->get_index($schema['columns']);
                case 'input' :
                    $input = $this->get_input($schema['columns']);
                case 'confirm' :
                case 'detail' :
                    $confirm = $this->get_confirm($schema['columns']);
                case 'complete' :
                    $name = 'view/' . $type;
                    break;
                default :
                    throw new Exception("Error: no view [view/$type].\n");
            }
            $template = $lib->template($name, array(
                '#table#' => $params['table'],
                '#Table#' => $class,
                '#table_id#' => $schema['table_id'],
                '#Table_id#' => ucwords($schema['table_id']),
                '#index_th#' => trim($index['th']),
                '#index_td#' => trim($index['td']),
                '#input#' => trim($input),
                '#confirm#' => trim($confirm),
                //'#css#' => $this->get_css($type),
            ));
            echo($template);
        } catch (Exception $e) {
            echo($e->getMessage());
        }
    }

    private function get_index($columns)
    {
        $html = array('th' => '', 'td' => '');
        $lib = lib('common');
        foreach ($columns as $col) {
            $field = $col['field'];
            $label = ucfirst($col['field']);
            if ($lib->skip_column($field)) { continue; }
            $html['th'] .= "<th>$label</th>\n";
            $html['td'] .= "<td><" . "?= \$val['$field'] ?" . "></td>\n";
        }
        return $html;
    }

    private function get_input($columns)
    {
        $html = '';
        foreach ($columns as $col) {
            $field = $col['field'];
            $label = ucfirst($col['field']);
            if ($col['key'] == 'PRI') {
                continue;
            }
            switch ($field) {
                case 'inserted_at' :
                case 'updated_at' :
                    continue;
                default :
                    $html .= "<tr>\n";
                    $html .= "<th>$label</th>\n";
                    $html .= "<td><" . "?= \$f->text('$field') ?" . "></td>\n";
                    $html .= "</tr>\n";
            }
        }
        return $html;
    }

    private function get_confirm($columns)
    {
        $html = '';
        foreach ($columns as $col) {
            $field = $col['field'];
            $label = ucfirst($col['field']);
            if ($col['key'] == 'PRI') {
                continue;
            }
            switch ($field) {
                case 'inserted_at' :
                case 'updated_at' :
                    continue;
                default :
                    $html .= "<tr>\n";
                    $html .= "<th>$label</th>\n";
                    $html .= "<td><" . "?= nl2br(\$f->fetch('$field')) ?" . "></td>\n";
                    $html .= "</tr>\n";
            }
        }
        return $html;
    }

    private function get_css($type)
    {
        switch ($type) {
            case 'index' :
                $css = lib('common')->template('view/index', array(), 'css');
                break;
            case 'confirm' :
            case 'detail' :
            case 'input' :
                $css = lib('common')->template('view/input', array(), 'css');
                break;
            default :
                $css = '';
        }
        return $css;
    }
}

