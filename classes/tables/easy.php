<?php

namespace local_dde\tables;

use html_writer;


class easy extends table
{

    private $easyFilter = '';

    //массив для фильтров
    function __construct($id, \html_table $table = null, $clearCache = false)                                                 //передаем id, чтобы не забивать SESSION
    {
        parent::__construct($id, $table, $clearCache);
        $this->easyFilter = optional_param('easyFilter', $this->loadData('easyFilter', ''), PARAM_TEXT);                //получаем либо из GET/POST либо из сохраненной сессии значение                                                                                                                 введеное в поиск easyFilter (filterValue)
        $this->saveData('easyFilter', $this->easyFilter);
    }

    //Создать отфильтрованную таблицу по значению easyFilter
    function filterData($data)                                                                                          //передаем $data - таблица, в которой искать и $filter - что ищем
    {
        if (empty($this->easyFilter)) return $data;

        $mas = [];
        $datastr = $this->getDataAsStr($data);
        //ETALON
//        foreach ($data as $tr => $d) {                                                                                         //для каждой записи из таблицы
//            if ((stripos(implode(',', $d), $this->easyFilter) !== false)) {                                             //если easyFilter(filterValue) содержится в массиве строки
//                $mas[] = $datastr[$tr];                                                                                            //формируем массив отфильтрованных данных (строки, совпадающие с запросом)
//            }
//        }
        /*DDD*/
        foreach ($datastr as $tr => $d) {                                                                                         //для каждой записи из таблицы
            if ((stripos(implode(',', $d), $this->easyFilter) !== false)) {                                             //если easyFilter(filterValue) содержится в массиве строки
                $mas[] = $datastr[$tr];                                                                                            //формируем массив отфильтрованных данных (строки, совпадающие с запросом)
            }
        }
        /*DDD*/
        $this->countrecords = count($mas);                                                                              //количество строк = количество строк в отфильтрованном массиве
        return $mas;                                                                                                    //возвращам отфильтрованный массив
    }




    function render()
    {
        $filter =
            html_writer::empty_tag('input', ['class' =>'mdb-input', 'name' => 'filter', 'placeholder' => 'Поиск по таблице', 'id' => 'filter' . $this->id, 'value' => $this->easyFilter])
        ; //div
        return parent::mainRender('', $filter); // TODO: Change the autogenerated stub
    }

}

?>