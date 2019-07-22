<?php


namespace local_dde\tables;

use html_writer;
use html_table;

abstract class table
{
    protected $table = null;
    protected $page;
    protected $perpage;
    protected $countrecords;
    protected $id = '';
    protected $ajax = false;
    protected $wasSorted = false;
    protected $rangePerpage = [10, 25, 50, 100];
    protected $ajaxUrl = "/local/tables/ajax.php";
    protected $setTable = false;
    protected $hiddensRows = [];

//    protected $setPerpage = false;


    function __construct($id, html_table $table = null, $clearCache = false)
    {
        $this->id = $id;
        if (empty($id))
            $this->id = 'newtable' . rand(0, 10000000);
        if ($clearCache)
            $this->clearCache();
        $this->perpage = optional_param('perpage', $this->loadData('perpage', 10), PARAM_INT);
        $this->page = optional_param('page', $this->loadData('page', 0), PARAM_INT);
        if ($table) {
            $this->table = $table;

        } else {
            $this->ajax = true;
//            $this->setPerpage = true;
            $this->table = $this->loadData('table');
        }
        $this->countrecords = count($this->table->data);
        $this->saveData('table', $this->table);
        $this->saveData('perpage', $this->perpage);
        $this->saveData('page', $this->page);
        if ($this->ajax)
            $this->setSorted();
        $this->hiddensRows = $this->loadData('hiddensRows', []);
    }

    public function setHiddenRow($n)
    {
        $this->hiddensRows[] = $n;
        $this->saveData('hiddensRows', $this->hiddensRows);
    }

    public function setAjaxUrl($url)
    {
        $this->ajaxUrl = $url;
    }

    function setTable($func)
    {
        $ob = new \stdClass();
        $obsort = [];
        if ($sorted = $this->loadData('sort')) {
            foreach ($sorted as $s)
                $obsort[$s[0]] = $s[2];
        }
        if ($filters = $this->loadData('filters')) {
            foreach ($filters as $f) {
                $ob->{$f[1]} = $f[5];
            }
        } elseif ($filters = $this->loadData('easyFilter'))
        {
            $ob = $filters;
        }
        list($this->table, $this->countrecords) = $func($ob, $obsort, $this->page, $this->perpage);
        $this->ajax = false;
        $this->setTable = true;
        $this->saveData('table', $this->table);
    }

    public function isAjax()
    {
        $this->ajax = true;
    }

    protected function loadData($key = '', $default = false)
    {
        $data = new \stdClass();
        if ($key && isset($_SESSION[$this->id])) {
            $data = unserialize($_SESSION[$this->id]);
            return isset($data->{$key}) ? $data->{$key} : $default;
        } elseif (isset($_SESSION[$this->id])) {
            $data = unserialize($_SESSION[$this->id]);
        } elseif ($key)
            return $default;
        return $data;
    }

    protected function saveData($key, $value = false)
    {
        if (is_object($key))
            $_SESSION[$this->id] = serialize($key);
        else {
            $ob = $this->loadData();
            $ob->{$key} = $value;
            $_SESSION[$this->id] = serialize($ob);
        }
    }

    protected function clearCache()
    {
        unset($_SESSION[$this->id]);
    }

    function addSort($num, $type = PARAM_TEXT, $position = false)
    {
        if ($sorted = $this->loadData('sort')) {
        }
        if ($this->wasSorted)
            $position = false;
        if ($position)
            $this->wasSorted = true;
        $this->sorters[$num] = [$num, $type, $position];
        $this->saveData('sort', $this->sorters);
    }

    protected function setSorted()
    {
        $sortStr = optional_param('sortedTable', null, PARAM_TEXT);
        if (($sortStr !== null) && ($sorted = $this->loadData('sort'))) {
            foreach ($sorted as $k => $v) {
                $sorted[$k][2] = false;
            }
            if ($sortStr) {
                $sortStr = explode('|', $sortStr);
                if (isset($sorted[$sortStr[0]]))
                    $sorted[$sortStr[0]][2] = $sortStr[1];
            }
            $this->saveData('sort', $sorted);
        }
    }

    protected function renderSortHeader($heads)
    {
        global $OUTPUT;
        if ($sorted = $this->loadData('sort')) {
            $mas = [];
            foreach ($heads as $k => $m) {
                $el = $m;
                if (isset($sorted[$k])) {
                    $type = $sorted[$k][2] ? $sorted[$k][2] : 0;
                    if ($type == 1)
                        $m .= html_writer::tag('span', html_writer::img($OUTPUT->pix_url("t/sort_asc"), 'up'));
                    if ($type == -1)
                        $m .= html_writer::tag('span', html_writer::img($OUTPUT->pix_url("t/sort_desc"), 'down'));
                    switch ($type) {
                        case 0:
                            $type = 1;
                            break;
                        case 1:
                            $type = -1;
                            break;
                        case -1:
                            $type = 0;
                            break;
                    }
                    $el = html_writer::link("#", $m, ['class' => 'sorted', 'col-num' => $k, 'sort-position' => $type]);
                }
                $mas[] = $el;
            }
            return $mas;
        }
        return $heads;
    }

    //Установка сортировок, считывает и проставляет значения в input непосредственно в разметку
    protected function renderSortInput()
    {
        $html = '';
        $val = '';
        if ($sorted = $this->loadData('sort')) {
            foreach ($sorted as $s) {
                if ($s[2]) {
                    $val = $s[0] . "|" . $s[2];
                }
            }
        }
        return html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'tablesort', 'value' => $val]);
    }

    //Сортировка данных по возрастанию/убыванию, возвращает массив отсортированных данных
    protected function sortingTable($data)
    {
        if ($sorted = $this->loadData('sort'))                                                                          //если есть установка сортировки
        {
            foreach ($sorted as $s)                                                                                     //для каждого значения массива с настройками
            {
                if ($s[2] && isset($data[$s[0]]))                                                                       //s[2]-направление s[0]-столбец
                {
                    $num = $s[0];                                                                                       //num-столбец
                    $position = $s[2];                                                                                  //position-направление
                    switch ($s[1])                                                                                      //s[1]-тип данных, число/строка
                    {
                        case PARAM_INT:                                                                                 //если число
                            usort($data, function ($first, $second) use ($num, $position) {
                                if ($position == 1) {                                                                    //position=1 - по возрастанию
                                    if ($first[$num] > $second[$num]) return 1;                                         //первый эл-т > второго, возвращается 1
                                    if ($first[$num] < $second[$num]) return -1;                                        //первый эл-т < второго: -1
                                    return 0;                                                                           //сдвиг идет по сравнениям, где +1
                                }
                                if ($first[$num] > $second[$num]) return -1;                                            //если s[2]=false (без сортировки) -сюда не дойдет, если -1:
                                if ($first[$num] < $second[$num]) return 1;                                             //обратная сортировка
                                return 0;
                            });

                            break;
                        case PARAM_TEXT:                                                                                //если текст
                            usort($data, function ($first, $second) use ($num, $position) {
                                if ($position == 1) {                                                                   //по возрастанию
                                    return strnatcmp($first[$num], $second[$num]);                                      //natural order сравнение, первый с последующим
                                }
                                return strnatcmp($second[$num], $first[$num]);                                          //если первый аргумент > второго (2я строка > первой), то 1, сдвиг
                            });

                            break;

                        case 'date':                                                                                 //если число
                            usort($data, function ($first, $second) use ($num, $position) {
                                if ($position == 1) {                                                                    //position=1 - по возрастанию
                                    if (strtotime($first[$num]) > strtotime($second[$num])) return 1;                                         //первый эл-т > второго, возвращается 1
                                    if (strtotime($first[$num]) < strtotime($second[$num])) return -1;                                        //первый эл-т < второго: -1
                                    return 0;                                                                           //сдвиг идет по сравнениям, где +1
                                }
                                if (strtotime($first[$num]) > strtotime($second[$num])) return -1;                                            //если s[2]=false (без сортировки) -сюда не дойдет, если -1:
                                if (strtotime($first[$num]) < strtotime($second[$num])) return 1;                                             //обратная сортировка
                                return 0;
                            });

                            break;
                    }
                }
            }
        }
        return $data;
    }

    protected function pagination($page, $perpage, $countrecord)                                                          //передаем страницу, perpage, количество записей (считается в js)
    {
        global $OUTPUT;
        return $OUTPUT->paging_bar($countrecord, $page, $perpage, '#');

    }

    //Добавить фильтр
    function addFilter($type, $name, $typedata, $cols, $length, $default = false, $opt = '', $attr = [])
    {
        $cols = $cols . '';
        if ($filters = $this->loadData('filters')) {
            if (isset($filters[$name]))
                $default = $filters[$name][5];
        }
        //                          0     1         2       3       4        5        6     7
        $this->filters[$name] = [$type, $name, $typedata, $cols, $length, $default, $opt, $attr];                              //вносим эти параметры в массив фильтров
        $this->saveData('filters', $this->filters);
        $this->hasEasyFilter = false;
    }

    //Вывод HTML кода фильтров
    protected function renderFilters()
    {
        $fs = [];
        foreach ($this->filters as $f) {
            $html = '';
            switch ($f[0]) {
                case "input":
                case "text":
                    $html = html_writer::empty_tag('input', ['class' => 'mdb-input', 'value' => $f[5], 'type' => 'text', 'placeholder' => $f[6], 'name' => $f[1]]);
                    break;
                case "button":
                    $html = html_writer::empty_tag('input', ['value' => $f[6], 'type' => 'button', 'class' => 'btn btn-default', 'name' => $f[1]]);
                    break;
                case "select":
                    //public static function select(array $options, $name, $selected = '', $nothing = array('' => 'choosedots'), array $attributes = null) {
                    //html_writer::select($options, 'perpage', $this->perpage, [], ['id' => 'perpage' . $this->id]);      //select для выбора perpage
                    $html = html_writer::select($f[6], $f[1], $f[5], []);
                    break;
                case "multiple":
                    $html = html_writer::select($f[6], $f[1], $f[5], '', ['multiple' => ''] + $f[7]);
                    break;
                case "checkbox":
                    if ($f[5] == 0)
                        $html = html_writer::empty_tag('input', ['type' => $f[0], 'name' => $f[1]]);
                    if ($f[5] == 1)
                        $html = html_writer::empty_tag('input', ['type' => $f[0], 'name' => $f[1], 'checked' => '']);
                    break;
                /*DDD*/
                case "datefrom":
                    $def = $f[5];
                    $html = html_writer::empty_tag('input', ['autocomplete' => 'off', 'class' => 'mdb-input datepicker', 'value' => $def, 'type' => 'date', 'placeholder' => 'Укажите дату с...', 'name' => $f[1]]);
                    break;
                case "dateto":
                    $def = $f[5];
                    $html = html_writer::empty_tag('input', ['autocomplete' => 'off', 'class' => 'mdb-input datepicker', 'value' => $def, 'type' => 'date', 'placeholder' => 'Укажите дату по...', 'name' => $f[1]]);
                    break;
                case "otherButton":
                    $html = html_writer::link($f[6], $f[5], ['class' => $f[1]]);
                    break;
                case "perpage":
                    $html = html_writer::div("{perpage}", '', $f[7]);
                    break;

                /*DDD*/
            }
            $fs[] = html_writer::div($html, 'span' . $f[4]);
        }
        return html_writer::div(implode('', $fs), 'row-fluid local_tables-filters');
    }

    function filterData($data)
    {
        return $data;
    }

    function getDataAsStr($data)
    {
        $newmas = [];
        foreach ($data as $tr)
        {
            $mas = [];
            $tds = $tr;
            if(is_object($tr))
            {
                $tds = $tr->cells;
            }
            foreach ($tds as $td)
            {
                if(is_object($td))
                {
                    $mas[] = $td->text;
                } else
                    $mas[] = $td;
            }
            $newmas[] = $mas;
        }
        return $newmas;
    }

    abstract function render();

    function setPerpage($range = [])
    {
        $this->rangePerpage = $range;
    }

    function renderPerpage()
    {
        if (empty($this->rangePerpage)) {
            $select = '';
        } else {
            $options = [];
            foreach ($this->rangePerpage as $i) {
                $options[$i] = $i;
            }

            $select = 'Показать по:&nbsp;' .
                html_writer::div(
                    html_writer::select($options, 'perpage', $this->perpage, [], ['id' => 'perpage' . $this->id, 'class' => 'select custom-select mdb-input']), 'num'
                );
            $this->perpage = $this->rangePerpage[0];
            $this->perpage = optional_param('perpage', $this->perpage, PARAM_INT);
        }
        return $select;
    }

    public function js()
    {
        global $PAGE;
        $PAGE->requires->js_call_amd("local_dde/tables", 'table', ['#' . $this->id]);
//        $PAGE->requires->js_call_amd('local_dde/datetimepicker', 'init', ['#' . $this->id." .datepicker", false, false]);
//        $PAGE->requires->js_call_amd('theme_sdo/datetimepicker', 'init', ['input']);

    }

    protected function mainRender($filters = '', $leftFilters = '')
    {
        $table = new html_table();                                                                                           //создаем объект старого класса (выше)
        $table->head = $this->renderSortHeader($this->table->head);                                                     //заменяем стандартные заголовки на ссылки сортировки
        $table->caption = $this->table->caption;

        $select = '';
        $select = $this->renderPerpage();                                                                                    //вывод perpage


        if ($this->setTable) {
            $table->data = $this->table->data;
        } else {
            $this->table->data = $this->filterData($this->table->data);
            $this->table->data = $this->sortingTable($this->table->data);                                               //есть фильтра или нет - переходи сюда и по необходимости сортирует

            $i = 0;
            foreach ($this->table->data as $data) {                                                                     //для каждой записи из таблицы
                if ($i >= $this->perpage * $this->page && $i < $this->perpage * $this->page + $this->perpage) {         //если perpage*page  <= i <  perpage*page + perpage (т.е. лежит в пределах возвращаемой таблицы)
                    $table->data[] = $data;                                                                             //наполняем массив таблицы массивами строк
                }
                $i++;                                                                                                   //переход к следующей записи
            }
        }

        if (!$this->setTable && $this->hiddensRows) {
            $newhead = $newmas = $newrows = [];
            foreach ($table->head as $k => $head) {
                if (!in_array($k, $this->hiddensRows))
                    $newhead[] = $head;
            }
            foreach ($table->data as $rows) {
                $newmas = [];
                foreach ($rows as $k => $td) {
                    if (!in_array($k, $this->hiddensRows))
                        $newmas[] = $td;
                }
                $newrows[] = $newmas;
            }
            $table->head = $newhead;
            $table->data = $newrows;
        }
        $table->attributes['class'] = 'va-m table table-striped table-bordered table-sm no-footer equals-width';

        if ($this->ajax) {
            return $this->renderSortInput() . html_writer::div(html_writer::table($table), 'table-con') . $this->pagination($this->page, $this->perpage, $this->countrecords);  //в ajax возвращается таблица+пагинация, код                                                                                                                               дальше не идет, в JS подставляется возвращенное
        }
        //Вывод HTML


        $controls = '';
        $select = '';
        $hasPerpage = true;
        if ($filters) {
            if(strstr($filters, "{perpage}") !== false)
            {
                $filters = str_replace("{perpage}", $this->renderPerpage(), $filters);
                $hasPerpage = false;
            }
            $controls .= html_writer::div($filters, 'first-div');
        }
        if($hasPerpage)
            $select = $this->renderPerpage();


        if ($leftFilters && $select)
            $controls .= html_writer::div($leftFilters, 'first-div') . html_writer::div($select, 'align-right');
        if ($leftFilters && !$select)
            $controls .= html_writer::div($leftFilters, 'first-div');
        elseif (!$leftFilters && $select)
            $controls .= html_writer::div($select, 'align-right');



        $html = html_writer::div(                                                                                       //выводим итоговый результат
            html_writer::div($controls, 'trs-divs') .
            html_writer::div($this->renderSortInput() . html_writer::div(html_writer::table($table), 'table-con table-caption') . $this->pagination($this->page, $this->perpage, $this->countrecords), 'local_tables-container', []), 'local_tables', ['id' => $this->id, 'table-class' => get_class($this), 'ajax-url' => $this->ajaxUrl]
        );
        return $html;
    }
}

?>