define(['jquery', 'core/modal_factory', 'core/templates', 'core/modal_events', 'local_dde/modal', 'core/loadingicon'], function ($, ModalFactory, tpl, ModalEvents, Modal, LoadingIcon) {


    return {
        init: function (modalSelector, modalID) {
            $(modalSelector).click(function () {
                var title = $(this).attr('modal-title');
                var body = $(this).attr('modal-body');
                var bodyajax = $(this).attr('modal-bodyajax');
                var afterAjaxAmd = $(this).attr('modal-afterajax-amd');
                var afterAjaxFunc = $(this).attr('modal-afterajax-func');
                var afterCloseAmd = $(this).attr('modal-afterclose-amd');
                var afterCloseFunc = $(this).attr('modal-afterclose-func');
                var afterOpenFunc = $(this).attr('modal-afteropen-func');
                var afterOpenAmd = $(this).attr('modal-afteropen-amd');
                if (bodyajax)
                    body = "";
                ModalFactory.create({
                    type: Modal.TYPE,
                    title: title,
                    body: body
                }).then(function (modal) {
                    var root = modal.getRoot();
                    root.on(ModalEvents.shown, function () {
                        if(afterOpenAmd && afterOpenFunc)
                        {
                            var adder = new Function('require(["' + afterOpenAmd + '"], function (amd) {amd.' + afterOpenFunc + '("' + modalID + '");});');
                            adder();
                        }
                        if (bodyajax) {
                            var loadingIcon = LoadingIcon.addIconToContainerWithPromise($(modal.body));
                            Y.io(bodyajax, {
                                method: 'POST',
                                on: {
                                    success: function (tid, response) {
                                        loadingIcon.resolve();
                                        $(modal.body).html(response.responseText);
                                        if (afterAjaxAmd && afterAjaxFunc) {
                                            // alert('require(["' + afterAjaxAmd + '"], function (amd) {amd.' + afterAjaxFunc + '("' + modalSelector + '");});');
                                            var adder = new Function('require(["' + afterAjaxAmd + '"], function (amd) {amd.' + afterAjaxFunc + '("' + modalID + '");});');
                                            adder();
                                        }
                                        // alert(response.responseText);
                                    }
                                }
                            });
                        }
                    });
                    root.on(ModalEvents.hidden, function () {
                        if(afterCloseAmd && afterCloseFunc)
                        {
                            var adder = new Function('require(["' + afterCloseAmd + '"], function (amd) {amd.' + afterCloseFunc + '("' + modalID + '");});');
                            adder();
                        }
                    });


                    root.addClass('dde-dialog').attr('dde-modalid', modalID);
                    modal.show();
                    //$(modal.body).html('532452323423 4234 234');

                });
                return false;
            })
        }

    };
});