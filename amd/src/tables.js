define(['jquery'], function ($) {
    return {
        updateTable: function (table, page) {
            var perpage = $('select[name="perpage"]', $(table)).val(); //считываем значение perpage в объекте table
            var tableid = $(table).attr('id');  //получить id таблицы (та, которую обрабатываем)
            var ajaxurl = $(table).attr('ajax-url');  //получить id таблицы (та, которую обрабатываем)
            var tableClass = $(table).attr('table-class');  //получить id таблицы (та, которую обрабатываем)
            var easyFilter = $('input[name="filter"]', $(table)).val();
            var reset = $('input[name="reset"]', $(table)).val();


            //Формируем массив для отправки ajax
            var params = {page: page};
            params['sortedTable'] = $('input[name="tablesort"]', $(table)).val(); //считываем значение у нового сформированного input

            //какие то отдельные input и select
            if (perpage)
                params['perpage'] = perpage;
            if (tableid)
                params['tableid'] = tableid;
            if (tableClass)
                params['tableclass'] = tableClass;
            if (easyFilter !== false)
                params['easyFilter'] = easyFilter;
            if (reset)
                params['reset'] = reset;

            //для всех input[type=text] и select внутри table = div.newtable объект
            $(".local_tables-filters input[type='date'], .local_tables-filters input[type='text'], .local_tables-filters select", $(table)).each(function () {
                var name = $(this).attr('name');
                var val = $(this).val();
                if (name != 'search')
                    params[name] = val;     //формируем массив вида {page: "page"}
            })

            //prop выдает true/false
            $(".local_tables-filters input[type='checkbox']", $(table)).each(function () {
                var name = $(this).attr('name');
                var val = $(this).prop('checked');
                if (val)
                    val = 1;
                else
                    val = 0;
                params[name] = val;     //формируем массив вида {page: "page", checkbox:0}
            })


            $('select[name="perpage"]', $(table)).prop('disabled', true);    //ставим disabled на селект на время запроса
            $(".local_tables-container", table).append("<div class='loader-container'><div class='loader'></div></div>"); //добавляем div  с картинкой, чтобы показать загрузку
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: params
            }).done(function (data) {
                $(".local_tables-container", table).html(data); //ищем .newtable-container в объекте table (тот, который обрабатываем) и внутрь него подставляем возврашенные от ajax данные (html таблица)
                $('select[name="perpage"]', $(table)).prop('disabled', false);   //убираем disabled на селект после выполнения
            });
        },
        table: function (el) {
            var app = this;

            $('input[name="filter"]', $(el)).keyup(function () {
                var table = $(this).closest('.local_tables');       //table = div.newtable объект
                app.updateTable(table, 0);
            });
            $('select[name="perpage"]', $(el)).on('change', function () {
                var table = $(this).closest('.local_tables');       //table = div.newtable объект
                app.updateTable(table, 0);
            });
            $(el).on('click', 'a.page-link, .paging a', function () {
                var href = $(this).attr('href');
                if(href) {
                    var mas = href.match(/page=([\d]+)/i);
                    var page = mas[1];
                    var table = $(this).closest('.local_tables');   //table = div.newtable объект
                    app.updateTable(table, page);                   //дополнительно передается page - на который кликаем
                }
                return false;                               //ссылка #, передаем false
            });
            $(el).on('click', 'a.sorted', function () {
                var num = $(this).attr('col-num');
                var position = $(this).attr('sort-position');
                var table = $(this).closest('.local_tables');   //table = div.newtable объект
                $('input[name="tablesort"]', $(table)).val(num + "|" + position);
                app.updateTable(table, 0);
                return false;                               //ссылка #, передаем false
            });

            $(el).on('click', 'input[name="clear"]', function () {
                var table = $(this).closest('.local_tables');   //table = div.newtable объект
                $('select[name="perpage"]', $(table)).val($('select[name="perpage"] option:first').val());
                $('.local_tables-filters select', $(table)).val($('.local_tables-filters select option:first').val());
                $('.local_tables-filters input[type="text"]', $(table)).val('');
                $(".local_tables-filters input[type='checkbox']", $(table)).prop('checked', false);
                app.updateTable(table, 0);
            });



//при изменении input
            $(".local_tables-filters input", $(el)).on('change keyup',function () {
                var table = $(this).closest('.local_tables');       //table = div.newtable объект
                if ($(this).attr('name') != 'search')
                    app.updateTable(table, 0);
            });

            //при изменении select
            $(".local_tables-filters select, .local_tables-filters input[type='checkbox']", $('.local_tables')).change(function () {
                var table = $(this).closest('.local_tables');       //table = div.newtable объект
                app.updateTable(table, 0);
            });
        }
    }
});