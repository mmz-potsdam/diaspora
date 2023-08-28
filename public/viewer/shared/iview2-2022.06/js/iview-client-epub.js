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
        var widgets;
        (function (widgets) {
            var epub;
            (function (epub) {
                var EpubStructureChapter = (function (_super) {
                    __extends(EpubStructureChapter, _super);
                    function EpubStructureChapter(parent, type, label, chapter, epubChapter) {
                        var _this = _super.call(this, parent, type, (epubChapter != null) ? epubChapter.href : 'root', label, chapter, new MyCoReMap(), function (c) { return c(null); }) || this;
                        _this.epubChapter = epubChapter;
                        return _this;
                    }
                    return EpubStructureChapter;
                }(mycore.viewer.model.StructureChapter));
                epub.EpubStructureChapter = EpubStructureChapter;
            })(epub = widgets.epub || (widgets.epub = {}));
        })(widgets = viewer.widgets || (viewer.widgets = {}));
    })(viewer = mycore.viewer || (mycore.viewer = {}));
})(mycore || (mycore = {}));
var mycore;
(function (mycore) {
    var viewer;
    (function (viewer) {
        var widgets;
        (function (widgets) {
            var epub;
            (function (epub) {
                var EpubStructureBuilder = (function () {
                    function EpubStructureBuilder() {
                    }
                    EpubStructureBuilder.prototype.convertToChapter = function (item, parent) {
                        var _this = this;
                        var chapters = [];
                        var chapter = new epub.EpubStructureChapter(parent, typeof parent === 'undefined' ? 'root' : '', item.label, chapters, item);
                        if (item.subitems != null) {
                            item.subitems
                                .map(function (childItem) {
                                return _this.convertToChapter(childItem, chapter);
                            })
                                .forEach(function (childChapter) {
                                chapters.push(childChapter);
                            });
                        }
                        return chapter;
                    };
                    return EpubStructureBuilder;
                }());
                epub.EpubStructureBuilder = EpubStructureBuilder;
            })(epub = widgets.epub || (widgets.epub = {}));
        })(widgets = viewer.widgets || (viewer.widgets = {}));
    })(viewer = mycore.viewer || (mycore.viewer = {}));
})(mycore || (mycore = {}));
var mycore;
(function (mycore) {
    var viewer;
    (function (viewer) {
        var components;
        (function (components) {
            var MyCoReEpubDisplayComponent = (function (_super) {
                __extends(MyCoReEpubDisplayComponent, _super);
                function MyCoReEpubDisplayComponent(epubSettings, appContainer) {
                    var _this = _super.call(this) || this;
                    _this.epubSettings = epubSettings;
                    _this.appContainer = appContainer;
                    _this.zoomInPercent = 100;
                    return _this;
                }
                Object.defineProperty(MyCoReEpubDisplayComponent.prototype, "handlesEvents", {
                    get: function () {
                        var handlesEvents = [];
                        if (this.epubSettings.doctype !== 'epub') {
                            return handlesEvents;
                        }
                        handlesEvents.push(mycore.viewer.widgets.toolbar.events.ButtonPressedEvent.TYPE);
                        handlesEvents.push(mycore.viewer.components.events.ChapterChangedEvent.TYPE);
                        handlesEvents.push(mycore.viewer.components.events.RequestStateEvent.TYPE);
                        handlesEvents.push(mycore.viewer.components.events.RestoreStateEvent.TYPE);
                        handlesEvents.push(mycore.viewer.components.events.ProvideToolbarModelEvent.TYPE);
                        return handlesEvents;
                    },
                    enumerable: true,
                    configurable: true
                });
                MyCoReEpubDisplayComponent.prototype.handle = function (e) {
                    if (this.epubSettings.doctype !== 'epub') {
                        return;
                    }
                    if (e.type === mycore.viewer.components.events.ChapterChangedEvent.TYPE) {
                        var cce = e;
                        this.handleChapterChangedEvent(cce);
                        return;
                    }
                    if (e.type === mycore.viewer.components.events.ProvideToolbarModelEvent.TYPE) {
                        var ptme = e;
                        this.handleProvideToolbarModelEvent(ptme);
                        return;
                    }
                    if (e.type === mycore.viewer.widgets.toolbar.events.ButtonPressedEvent.TYPE) {
                        var buttonPressedEvent = e;
                        this.handleButtonPressedEvent(buttonPressedEvent);
                        return;
                    }
                    if (e.type === mycore.viewer.components.events.RequestStateEvent.TYPE) {
                        var rse = e;
                        this.handleRequestStateEvent(rse);
                        return;
                    }
                    if (e.type === mycore.viewer.components.events.RestoreStateEvent.TYPE) {
                        var rse = e;
                        if (rse.restoredState.has('start')) {
                            this.rendition.display(rse.restoredState.get('start'));
                        }
                        return;
                    }
                };
                MyCoReEpubDisplayComponent.prototype.init = function () {
                    var _this = this;
                    if (this.epubSettings.doctype !== 'epub') {
                        return;
                    }
                    this.trigger(new mycore.viewer.components.events.WaitForEvent(this, mycore.viewer.components.events.ProvideToolbarModelEvent.TYPE));
                    var content = document.createElement('div');
                    content.style.width = '100%';
                    content.style.height = '100%';
                    content.style.background = 'white';
                    content.style.paddingLeft = '5%';
                    this.trigger(new mycore.viewer.components.events.ShowContentEvent(this, jQuery(content), mycore.viewer.components.events.ShowContentEvent.DIRECTION_CENTER));
                    var book = ePub(this.epubSettings.epubPath, {
                        openAs: 'directory',
                        method: 'goToTheElse!'
                    });
                    this.rendition = book.renderTo(content, {
                        manager: 'continuous',
                        flow: 'scrolled',
                        width: '100%',
                        height: '100%',
                        offset: 1
                    });
                    this.rendition.display();
                    var resizeBounce = new Debounce(100, function (b) {
                        _this.rendition.resize();
                    });
                    jQuery(content).bind('iviewResize', function () {
                        resizeBounce.call(false);
                    });
                    var idChapterMap = new MyCoReMap();
                    book.loaded.navigation.then(function (toc) {
                        var children = [];
                        var root = new viewer.widgets.epub.EpubStructureChapter(null, 'root', _this.epubSettings.filePath, children, null);
                        var builder = new viewer.widgets.epub.EpubStructureBuilder();
                        toc.forEach(function (item) {
                            children.push(builder.convertToChapter(item, root));
                        });
                        var model = new mycore.viewer.model.StructureModel(root, [], new MyCoReMap(), new MyCoReMap(), new MyCoReMap(), false);
                        var addToMap;
                        addToMap = function (chapter) {
                            idChapterMap.set(chapter.id, chapter);
                            chapter.chapter.forEach(function (child) {
                                addToMap(child);
                            });
                        };
                        addToMap(root);
                        _this.trigger(new mycore.viewer.components.events.StructureModelLoadedEvent(_this, model));
                    });
                    this.rendition.on('relocated', function (section) {
                        if (section.start.href !== _this.currentHref) {
                            _this.currentHref = section.start.href;
                        }
                    });
                    this.trigger(new mycore.viewer.components.events.WaitForEvent(this, mycore.viewer.components.events.RestoreStateEvent.TYPE));
                };
                MyCoReEpubDisplayComponent.prototype.handleRequestStateEvent = function (rse) {
                    var location = this.rendition.currentLocation();
                    rse.stateMap.set('start', location.start.cfi);
                    rse.stateMap.set('end', location.end.cfi);
                };
                MyCoReEpubDisplayComponent.prototype.handleButtonPressedEvent = function (buttonPressedEvent) {
                    if (buttonPressedEvent.button.id === 'ZoomInButton') {
                        this.zoomInPercent += 25;
                        this.rendition.themes.fontSize(this.zoomInPercent + "%");
                    }
                    if (buttonPressedEvent.button.id === 'ZoomOutButton') {
                        this.zoomInPercent -= 25;
                        this.rendition.themes.fontSize(this.zoomInPercent + "%");
                    }
                };
                MyCoReEpubDisplayComponent.prototype.handleProvideToolbarModelEvent = function (ptme) {
                    ptme.model._zoomControllGroup.removeComponent(ptme.model._rotateButton);
                    ptme.model._zoomControllGroup.removeComponent(ptme.model._zoomFitButton);
                    ptme.model._zoomControllGroup.removeComponent(ptme.model._zoomWidthButton);
                    ptme.model.removeGroup(ptme.model._imageChangeControllGroup);
                    var lcg = ptme.model.getGroup(ptme.model._layoutControllGroup.name);
                    if (lcg !== null && typeof lcg !== 'undefined') {
                        ptme.model.removeGroup(ptme.model._layoutControllGroup);
                    }
                };
                MyCoReEpubDisplayComponent.prototype.handleChapterChangedEvent = function (cce) {
                    var chapter = cce.chapter;
                    if (chapter != null || typeof chapter !== 'undefined') {
                        this.currentHref = chapter.id;
                        if (chapter.epubChapter !== null && cce.component !== this) {
                            this.rendition.display(chapter.epubChapter.href);
                        }
                    }
                };
                return MyCoReEpubDisplayComponent;
            }(components.ViewerComponent));
            components.MyCoReEpubDisplayComponent = MyCoReEpubDisplayComponent;
        })(components = viewer.components || (viewer.components = {}));
    })(viewer = mycore.viewer || (mycore.viewer = {}));
})(mycore || (mycore = {}));
addViewerComponent(mycore.viewer.components.MyCoReEpubDisplayComponent);
