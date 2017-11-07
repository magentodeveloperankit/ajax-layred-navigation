var TrainingLayredHandler = {
    listenersBinded: false,
    isAjaxEnabled: false,
    handleEvent: function (el, event) {
        var url;
        var self = this;
        if (el.tagName.toLowerCase() === 'input') {
            url = jQuery(el).val();
        } else if (el.tagName.toLowerCase() === 'a') {
            url = jQuery(el).attr('href');
        } else if (el.tagName.toLowerCase() === 'select') {
            url = jQuery(el).val();
        }

        if (jQuery(el).hasClass('no-ajax')) {
            window.location.href = url;
            return;
        }

        self.sendAjaxRequest(url);

        if (event) {
            event.preventDefault();
        }
    },
    sendAjaxRequest: function(url) {
        var fullUrl;
        var self = this;
        // Add this to query string for full page caching systems
        if (url.indexOf('?') != -1) {
            fullUrl = url + '&isLayerAjax=1';
        } else {
            fullUrl = url + '?isLayerAjax=1';
        }

        $('loading').show();
        $('ajax-errors').hide();

        self.pushState(null, url, false);

        new Ajax.Request(fullUrl, {
            method: 'get',
            onSuccess: function (transport) {
                if (transport.responseJSON) {
                    $('catalog-listing').update(transport.responseJSON.listing);
                    $('layered-navigation').update(transport.responseJSON.layer);
                    self.pushState({
                        listing: transport.responseJSON.listing,
                        layer: transport.responseJSON.layer
                    }, url, true);
                    self.ajaxListener();
                    self.blockCollapsing();

                    if (typeof(ConfigurableSwatchesList) !== 'undefined') {
                        setTimeout(function(){
                            jQuery(document).trigger('product-media-loaded');
                        }, 0);
                    }
                } else {
                    $('ajax-errors').show();
                }
                $('loading').hide();
            },
            onComplete: TrainingLayredHandler.sendUpdateEvent
        });
    },
    sendUpdateEvent: function() {
        jQuery(document).trigger('training:updatePage');
    },
    pushState: function (data, link, replace) {
        var History = window.History;
        if (!History.enabled) {
            return false;
        }

        if (replace) {
            History.replaceState(data, document.title, link);
        } else {
            History.pushState(data, document.title, link);
        }
    },
    ajaxListener: function () {
        var self = this;
        var els;
        els = $$('div.pager a').concat(
            $$('div.sorter a'),
            $$('div.pager select'),
            $$('div.sorter select'),
            $$('div.block-layered-nav a'),
            $$('div.block-layered-nav input[type="checkbox"]','div.block-layered-nav input[type="radio"]')
        );
        els.each(function (el) {
            var tagName = el.tagName.toLowerCase();
            if (tagName === 'a') {
                $(el).observe('click', function (event) {
                    self.handleEvent(this, event);
                });
            } else if (tagName === 'select' || tagName === 'input') {
                $(el).setAttribute('onchange', '');
                $(el).observe('change', function (event) {
                    self.handleEvent(this, event);
                });
            }
        });
    },

    bindListeners: function () {
        var self = this;
        if (self.listenersBinded || !self.isAjaxEnabled) {
            return false;
        }
        self.listenersBinded = true;
        document.observe("dom:loaded", function () {
            self.ajaxListener();

            (function (History) {
                // Skip empty categories.
                if (!History.enabled || !$('catalog-listing')) {
                    return false;
                }

                self.pushState({
                    listing: $('catalog-listing').innerHTML,
                    layer: $('layered-navigation').innerHTML
                }, document.location.href, true);

                // Bind to StateChange Event
                History.Adapter.bind(window, 'popstate', function (event) {
                    if (event.type == 'popstate') {
                        var State = History.getState();
                        $('catalog-listing').update(State.data.listing);
                        $('layered-navigation').update(State.data.layer);
                        self.ajaxListener();
                        self.blockCollapsing();

                        if (typeof(ConfigurableSwatchesList) !== 'undefined') {
                            setTimeout(function(){
                                jQuery(document).trigger('product-media-loaded');
                            }, 0);
                        }
                    }
                });
            })(window.History);
        });
    },

    blockCollapsing: function() {
        // ==============================================
        // Block collapsing (on smaller viewports)
        // ==============================================

        if (typeof(enquire) !== 'undefined') {
            enquire.register('(max-width: ' + bp.medium + 'px)', {
                setup: function () {
                    this.toggleElements = jQuery(
                        // This selects the menu on the My Account and CMS pages
                        '.col-left-first .block:not(.block-layered-nav) .block-title, ' +
                        '.col-left-first .block-layered-nav .block-subtitle--filter, ' +
                        '.sidebar:not(.col-left-first) .block .block-title'
                    );
                },
                match: function () {
                    this.toggleElements.toggleSingle();
                },
                unmatch: function () {
                    this.toggleElements.toggleSingle({destruct: true});
                }
            });
        }
    },

}
