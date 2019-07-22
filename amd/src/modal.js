define(['jquery', 'core/notification', 'core/custom_interaction_events', 'core/modal', 'core/modal_events', 'core/modal_registry'],
    function($, Notification, CustomEvents, mModal, ModalEvents, ModalRegistry) {


        var registered = false;

        var SELECTORS = {
            CLOSE_BUTTON: '[data-action="close"]',
        };

        /**
         * Constructor for the Modal.
         *
         * @param {object} root The root jQuery element for the modal
         */
        var Modal = function(root) {
            mModal.call(this, root);

            if (!this.getFooter().find(SELECTORS.CLOSE_BUTTON).length) {
                Notification.exception({message: 'No cancel button found'});
            }
        };
        Modal.TYPE = 'DDEMODAL';
        Modal.prototype = Object.create(mModal.prototype);
        Modal.prototype.constructor = Modal;

        /**
         * Override parent implementation to prevent changing the footer content.
         */
        Modal.prototype.setFooter = function() {
            Notification.exception({message: 'Can not change the footer of a cancel modal'});
            return;
        };

        /**
         * Set up all of the event handling for the modal.
         *
         * @method registerEventListeners
         */
        Modal.prototype.registerEventListeners = function() {
            // Apply parent event listeners.
            mModal.prototype.registerEventListeners.call(this);

            this.getModal().on(CustomEvents.events.activate, SELECTORS.CLOSE_BUTTON, function(e, data) {
                var cancelEvent = $.Event(ModalEvents.cancel);
                this.getRoot().trigger(cancelEvent, this);

                if (!cancelEvent.isDefaultPrevented()) {
                    this.hide();
                    data.originalEvent.preventDefault();
                }
            }.bind(this));
        };

        if (!registered) {
            ModalRegistry.register(Modal.TYPE, Modal, 'local_dde/modal');
            registered = true;
        }

        return Modal;
    });
