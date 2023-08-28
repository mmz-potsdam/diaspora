var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
var mycore;
(function (mycore) {
    var viewer;
    (function (viewer) {
        var components;
        (function (components) {
            var MyCoReMetadataComponent = (function (_super) {
                __extends(MyCoReMetadataComponent, _super);
                function MyCoReMetadataComponent(_settings) {
                    var _this = _super.call(this) || this;
                    _this._settings = _settings;
                    _this._spinner = jQuery("<img />");
                    _this._enabled = true;
                    return _this;
                }
                MyCoReMetadataComponent.prototype.init = function () {
                    var _this = this;
                    this._container = jQuery("<div></div>");
                    this._container.addClass("card-body");
                    if (typeof this._settings.metadataURL != "undefined" && this._settings.metadataURL != null) {
                        var metadataUrl = ViewerFormatString(this._settings.metadataURL, {
                            derivateId: this._settings.derivate,
                            objId: this._settings.objId
                        });
                        this._container.load(metadataUrl, {}, function () {
                            _this.correctScrollPosition();
                        });
                    }
                    else if ("metsURL" in this._settings) {
                        var xpath = "/mets:mets/*/mets:techMD/mets:mdWrap[@OTHERMDTYPE='MCRVIEWER_HTML']/mets:xmlData/*";
                        var metsURL = this._settings.metsURL;
                        var settings = {
                            url: metsURL,
                            success: function (response) {
                                var htmlElement = singleSelectShim(response, xpath, XMLUtil.NS_MAP);
                                if (htmlElement != null) {
                                    if ("xml" in htmlElement) {
                                        htmlElement = jQuery(htmlElement.xml);
                                    }
                                    _this._container.append(htmlElement);
                                }
                                else {
                                    _this._container.remove();
                                }
                            },
                            error: function (request, status, exception) {
                                console.log(status);
                                console.error(exception);
                            }
                        };
                        jQuery.ajax(settings);
                    }
                    else {
                        this._enabled = false;
                        return;
                    }
                    this.trigger(new components.events.ComponentInitializedEvent(this));
                    this.trigger(new components.events.WaitForEvent(this, components.events.ShowContentEvent.TYPE));
                };
                MyCoReMetadataComponent.prototype.correctScrollPosition = function () {
                    if (this._container.parent().scrollTop() > 0) {
                        var containerHeightDiff = this._container.height();
                        var parent = this._container.parent();
                        parent.scrollTop(parent.scrollTop() + containerHeightDiff);
                    }
                };
                MyCoReMetadataComponent.prototype.handle = function (e) {
                    if (this._enabled && e.type == components.events.ShowContentEvent.TYPE) {
                        var sce = e;
                        if (sce.component instanceof components.MyCoReChapterComponent) {
                            sce.content.prepend(this._container);
                        }
                    }
                };
                Object.defineProperty(MyCoReMetadataComponent.prototype, "handlesEvents", {
                    get: function () {
                        return [components.events.ShowContentEvent.TYPE];
                    },
                    enumerable: true,
                    configurable: true
                });
                return MyCoReMetadataComponent;
            }(components.ViewerComponent));
            components.MyCoReMetadataComponent = MyCoReMetadataComponent;
        })(components = viewer.components || (viewer.components = {}));
    })(viewer = mycore.viewer || (mycore.viewer = {}));
})(mycore || (mycore = {}));
addViewerComponent(mycore.viewer.components.MyCoReMetadataComponent);
console.log("METADATA MODULE");
