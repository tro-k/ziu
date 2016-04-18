<?php

class Model
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
            $template = $lib->template('model', array(
                '#table#' => $params['table'],
                '#Table#' => $class,
                '#table_id#' => $schema['table_id'],
                '#validate#' => $this->validate($schema['columns']),
                '#get_list#' => $this->get_list($schema['columns']),
                '#insert#' => $this->insert($schema['columns']),
                '#update#' => $this->update($schema['columns']),
            ));
            echo($template);
        } catch (Exception $e) {
            echo($e->getMessage());
        }
    }

    private function get_list($columns)
    {
        $str = '';
        foreach ($columns as $col) {
            $field = $col['field'];
            list($type) = explode('(', $col['type']);
            switch ($type) {
                case 'char' :
                case 'varchar' :
                    $tmp = "\$sn = \$sn->where(array('$field', 'like', \$keys['$field'] . '%'));";
                    break;
                case 'text' :
                    $tmp = "\$sn = \$sn->where(array('$field', 'like', '%' . \$keys['$field'] . '%'));";
                    break;
                case 'int' :
                    $tmp = "\$sn = \$sn->where(array('$field', \$keys['$field']));";
                    break;
                default :
                    $tmp = '';
            }
            if (! empty($tmp)) {
                $str .= "        if (! empty(\$keys['$field'])) {\n";
                $str .= "            $tmp\n";
                $str .= "        }\n";
            }
        }
        return trim($str);
    }

    private function insert($columns)
    {
        $str = '';
        foreach ($columns as $col) {
            $field = $col['field'];
            switch ($field) {
                case 'inserted_at' :
                    $tmp = "\$data['$field'] = date('Y-m-d H:i:s');";
                    break;
                default :
                    $tmp = '';
            }
            if (! empty($tmp)) {
                $str .= "        $tmp\n";
            }
        }
        return trim($str);
    }

    private function update($columns)
    {
        $str = '';
        foreach ($columns as $col) {
            $field = $col['field'];
            switch ($field) {
                case 'updated_at' :
                    $tmp = "\$data['$field'] = date('Y-m-d H:i:s');";
                    break;
                default :
                    $tmp = '';
            }
            if (! empty($tmp)) {
                $str .= "        $tmp\n";
            }
        }
        return trim($str);
    }

    private function validate($columns)
    {
        $str = '';
        $lib = lib('common');
        foreach ($columns as $col) {
            $field = $col['field'];
            $label = $col['comment'] === '' ? ucfirst($col['field']) : $col['comment'];
            if ($lib->skip_column($field)) { continue; }
            $valid = array();
            $col['null'] == 'NO' && $valid[] = 'required';
            $col['key'] == 'PRI' && $col['type'] == 'int(11)' && $valid[] = 'nozero';
            if (preg_match('/^varchar\((\d+)\)$/', $col['type'], $match)) {
                $valid[] = "maxlen[{$match[1]}]";
            }
            $col['type'] == 'date' && $valid[] = 'date';
            $col['type'] == 'time' && $valid[] = 'time';
            $rule = implode('|', $valid);
            if (! empty($rule)) {
                $tmp = "\$vd->field('$field', '$label', '$rule');";
            } else {
                $tmp = "// \$vd->field('$field', '$label', 'required');";
            }
            if ($col['key'] == 'PRI') {
                $str .= "        if (\$id !== FALSE) {\n";
                $str .= "            $tmp\n";
                $str .= "        }\n";
            } else {
                $str .= "        $tmp\n";
            }
        }
        return trim($str);
    }

}

