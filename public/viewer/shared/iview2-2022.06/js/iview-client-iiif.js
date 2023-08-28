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
            var iiif;
            (function (iiif) {
                var IIIFStructureModel = (function (_super) {
                    __extends(IIIFStructureModel, _super);
                    function IIIFStructureModel(smLinkMap, rootChapter, imageList, chapterToImageMap, imageToChapterMap, imageHrefImageMap) {
                        var _this = _super.call(this, rootChapter, imageList, chapterToImageMap, imageToChapterMap, imageHrefImageMap, false) || this;
                        _this.smLinkMap = smLinkMap;
                        return _this;
                    }
                    return IIIFStructureModel;
                }(viewer.model.StructureModel));
                iiif.IIIFStructureModel = IIIFStructureModel;
            })(iiif = widgets.iiif || (widgets.iiif = {}));
        })(widgets = viewer.widgets || (viewer.widgets = {}));
    })(viewer = mycore.viewer || (mycore.viewer = {}));
})(mycore || (mycore = {}));
var mycore;
(function (mycore) {
    var viewer;
    (function (viewer) {
        var widgets;
        (function (widgets) {
            var iiif;
            (function (iiif) {
                var IIIFStructureBuilder = (function () {
                    function IIIFStructureBuilder(manifestDocument, tilePathBuilder, imageAPIURL) {
                        this.manifestDocument = manifestDocument;
                        this.tilePathBuilder = tilePathBuilder;
                        this.imageAPIURL = imageAPIURL;
                        this.hrefResolverElement = document.createElement('a');
                    }
                    IIIFStructureBuilder.prototype.processManifest = function () {
                        this.vIdFileMap = this.getIdFileMap();
                        var useFilesMap = new MyCoReMap();
                        this.vChapterIdMap = new MyCoReMap();
                        this.vIdPhysicalFileMap = undefined;
                        this.vSmLinkMap = new MyCoReMap();
                        this.vChapterImageMap = new MyCoReMap();
                        this.vImageChapterMap = new MyCoReMap();
                        this.vImprovisationMap = new MyCoReMap();
                        this.vManifestChapter = this.processChapter(null, this.manifestDocument.getTopRanges()[0], this.manifestDocument.getSequences()[0].getCanvases());
                        this.vImageHrefImageMap = new MyCoReMap();
                        this.vImageList = [];
                        this.vIdImageMap = new MyCoReMap();
                        this.processImages();
                        this.vStructureModel = new widgets.iiif.IIIFStructureModel(this.vSmLinkMap, this.vManifestChapter, this.vImageList, this.vChapterImageMap, this.vImageChapterMap, this.vImageHrefImageMap);
                        return this.vStructureModel;
                    };
                    IIIFStructureBuilder.prototype.processChapter = function (parent, chapter, cans) {
                        var _this = this;
                        var chapterObject;
                        if (chapter === undefined || (chapter.getCanvasIds().length === 0 && chapter.getRanges().length === 0)) {
                            chapterObject = new viewer.model.StructureChapter(parent, '', 'LOG_0', this.manifestDocument.getDefaultLabel());
                            this.vChapterIdMap.set(chapterObject.id, chapterObject);
                            cans.forEach(function (can) {
                                var childChap = new viewer.model.StructureChapter(chapterObject, chapterObject, _this.getIDFromURL(can.id), can.getDefaultLabel());
                                chapterObject.chapter.push(childChap);
                                _this.vChapterIdMap.set(childChap.id, childChap);
                            });
                        }
                        else {
                            chapterObject = new viewer.model.StructureChapter(parent, '', this.getIDFromURL(chapter.id), chapter.getDefaultLabel());
                            this.vChapterIdMap.set(chapterObject.id, chapterObject);
                            chapter.getRanges().forEach(function (childChap) {
                                chapterObject.chapter.push(_this.processChapter(chapterObject, childChap, []));
                            });
                        }
                        return chapterObject;
                    };
                    IIIFStructureBuilder.prototype.getIdFileMap = function () {
                        var map = new MyCoReMap();
                        this.manifestDocument.getSequences()[0].getCanvases().forEach(function (canvas) {
                            canvas.getImages().forEach(function (image) {
                                if (image.id === undefined) {
                                    map.set(image.getResource().id, image);
                                }
                                else {
                                    map.set(image.id, image);
                                }
                            });
                        });
                        return map;
                    };
                    IIIFStructureBuilder.prototype.processImages = function () {
                        var _this = this;
                        var count = 1;
                        this.manifestDocument.getSequences()[0].getCanvases().forEach(function (canvas) {
                            var image = _this.parseFile(canvas, count++);
                            if (image !== null) {
                                _this.vImageList.push(image);
                                _this.vIdImageMap.set(_this.getIDFromURL(canvas.id), image);
                            }
                        });
                        this.vImageList = this.vImageList.sort(function (x, y) { return x.order - y.order; });
                        if (this.manifestDocument.getTopRanges().length > 0) {
                            this.makeLinks(this.manifestDocument.getTopRanges()[0]);
                        }
                        else {
                            this.makeLinksWithoutStructures(this.manifestDocument.getSequences()[0].getCanvases());
                        }
                        this.vImageList = this.vImageList.filter(function (el) { return _this.vImageChapterMap.has(el.id); });
                        this.vImageList.forEach(function (image, i) {
                            image.order = i + 1;
                            _this.vImageHrefImageMap.set(image.href, image);
                        });
                    };
                    IIIFStructureBuilder.prototype.makeLinks = function (elem) {
                        var _this = this;
                        var chapter = elem;
                        if (elem.getCanvasIds().length === 0 && elem.getRanges().length === 0) {
                            this.makeLinksWithoutStructures(this.manifestDocument.getSequences()[0].getCanvases());
                        }
                        elem.getCanvasIds().forEach(function (can) {
                            _this.makeLink(_this.vChapterIdMap.get(_this.getIDFromURL(chapter.id)), _this.vIdImageMap.get(_this.getIDFromURL(can)));
                        });
                        elem.getRanges().forEach(function (range) {
                            _this.makeLinks(range);
                        });
                    };
                    IIIFStructureBuilder.prototype.makeLinksWithoutStructures = function (cans) {
                        var _this = this;
                        cans.forEach(function (can) {
                            _this.makeLink(_this.vChapterIdMap.get(_this.getIDFromURL(can.id)), _this.vIdImageMap.get(_this.getIDFromURL(can.id)));
                        });
                    };
                    IIIFStructureBuilder.prototype.makeLink = function (chapter, image) {
                        if (chapter.parent !== null && !this.vChapterImageMap.has(chapter.parent.id)) {
                            this.vImprovisationMap.set(chapter.parent.id, true);
                            this.vChapterImageMap.set(chapter.parent.id, image);
                        }
                        if (!this.vChapterImageMap.has(chapter.id)
                            || this.vImageList.indexOf(this.vChapterImageMap.get(chapter.id)) > this.vImageList.indexOf(image)
                            || (this.vImprovisationMap.has(chapter.id) && this.vImprovisationMap.get(chapter.id))) {
                            this.vChapterImageMap.set(chapter.id, image);
                            this.vImprovisationMap.set(chapter.id, false);
                        }
                        if (!this.vImageChapterMap.has(image.id)) {
                            this.vImageChapterMap.set(image.id, chapter);
                        }
                        if (!this.vSmLinkMap.has(chapter.id)) {
                            this.vSmLinkMap.set(chapter.id, []);
                        }
                        this.vSmLinkMap.get(chapter.id).push(image.href);
                    };
                    IIIFStructureBuilder.prototype.parseFile = function (canvas, defaultOrder) {
                        var _this = this;
                        var type = 'page';
                        var id = this.getHrefFromID(this.getIDFromURL(canvas.id));
                        var order = parseInt('' + defaultOrder, 10);
                        var orderLabel = canvas.getDefaultLabel();
                        var contentIds = '';
                        var additionalHrefs = new MyCoReMap();
                        var imgHref = null;
                        var width = null;
                        var height = null;
                        var imgMimeType = null;
                        canvas.getImages().forEach(function (image) {
                            var href = image.getResource().getServices()[0].id;
                            var mimetype = image.getResource().getFormat() ? image.getResource().getFormat().toString() : null;
                            width = image.getResource().getWidth();
                            height = image.getResource().getHeight();
                            imgHref = href.substr(href.indexOf(_this.imageAPIURL) + _this.imageAPIURL.length);
                            imgMimeType = mimetype;
                        });
                        if (imgHref === null) {
                            console.warn('Unable to find MASTER|IVIEW2 file for ' + id);
                            return null;
                        }
                        return new viewer.model.StructureImage(type, id, order, orderLabel, imgHref, imgMimeType, function (cb) {
                            cb(_this.tilePathBuilder(imgHref, width, height));
                        }, additionalHrefs, contentIds, width, height);
                    };
                    IIIFStructureBuilder.prototype.getIDFromURL = function (url) {
                        return url.substr(url.lastIndexOf('/') + 1);
                    };
                    IIIFStructureBuilder.prototype.getHrefFromID = function (url) {
                        if (url.indexOf('%2F') > -1) {
                            return url.substr(url.indexOf('%2F') + 3);
                        }
                        return url;
                    };
                    return IIIFStructureBuilder;
                }());
                iiif.IIIFStructureBuilder = IIIFStructureBuilder;
            })(iiif = widgets.iiif || (widgets.iiif = {}));
        })(widgets = viewer.widgets || (viewer.widgets = {}));
    })(viewer = mycore.viewer || (mycore.viewer = {}));
})(mycore || (mycore = {}));
var mycore;
(function (mycore) {
    var viewer;
    (function (viewer) {
        var widgets;
        (function (widgets) {
            var iiif;
            (function (iiif) {
                var IviewIIIFProvider = (function () {
                    function IviewIIIFProvider() {
                    }
                    IviewIIIFProvider.loadModel = function (manifestDocumentLocation, imageAPIURL, tilePathBuilder) {
                        var promise = new ViewerPromise();
                        var settings = {
                            url: manifestDocumentLocation,
                            success: function (response) {
                                var manifest = manifesto.create(response);
                                var builder = new iiif.IIIFStructureBuilder(manifest, tilePathBuilder, imageAPIURL);
                                promise.resolve({ model: builder.processManifest(), document: response });
                            },
                            error: function (request, status, exception) {
                                promise.reject(exception);
                            }
                        };
                        jQuery.ajax(settings);
                        return promise;
                    };
                    return IviewIIIFProvider;
                }());
                iiif.IviewIIIFProvider = IviewIIIFProvider;
            })(iiif = widgets.iiif || (widgets.iiif = {}));
        })(widgets = viewer.widgets || (viewer.widgets = {}));
    })(viewer = mycore.viewer || (mycore.viewer = {}));
})(mycore || (mycore = {}));
var mycore;
(function (mycore) {
    var viewer;
    (function (viewer) {
        var components;
        (function (components) {
            var ShowContentEvent = mycore.viewer.components.events.ShowContentEvent;
            var MyCoReStructFileComponent = (function (_super) {
                __extends(MyCoReStructFileComponent, _super);
                function MyCoReStructFileComponent(settings, container) {
                    var _this = _super.call(this) || this;
                    _this.settings = settings;
                    _this.container = container;
                    _this.errorSync = Utils.synchronize([function (context) {
                            return context.lm != null && context.error;
                        }], function (context) {
                        new mycore.viewer.widgets.modal.ViewerErrorModal(_this.settings.mobile, context.lm.getTranslation('noStructFileShort'), context.lm.getFormatedTranslation('noStructFile', '<a href="mailto:"'
                            + _this.settings.adminMail + '>' + _this.settings.adminMail + '</a>'), _this.settings.webApplicationBaseURL + '/modules/iview2/img/sad-emotion-egg.jpg', _this.container[0]).show();
                        context.trigger(new ShowContentEvent(_this, jQuery(), mycore.viewer.widgets.layout.IviewBorderLayout.DIRECTION_WEST, 0));
                    });
                    _this.error = false;
                    _this.lm = null;
                    _this.mm = null;
                    return _this;
                }
                MyCoReStructFileComponent.prototype.postProcessChapter = function (chapter) {
                    var _this = this;
                    if (chapter.label === null || typeof chapter.label === 'undefined' || chapter.label === '') {
                        if (chapter.type !== null && typeof chapter.type !== 'undefined' && chapter.type !== '') {
                            var translationKey = this.buildTranslationKey(chapter.type || '');
                            if (this.lm.hasTranslation(translationKey)) {
                                chapter._label = this.lm.getTranslation(translationKey);
                            }
                        }
                    }
                    chapter.chapter.forEach(function (chap) {
                        _this.postProcessChapter(chap);
                    });
                };
                MyCoReStructFileComponent.prototype.buildTranslationKey = function (type) {
                    return 'dfgStructureSet.' + type.replace('- ', '');
                };
                MyCoReStructFileComponent.prototype.structFileLoaded = function (structureModel) {
                    this.postProcessChapter(structureModel._rootChapter);
                    var ev = new components.events.StructureModelLoadedEvent(this, structureModel);
                    this.trigger(ev);
                    this.vStructFileLoaded = true;
                    this.vEventToTrigger = ev;
                    var href = this.settings.filePath;
                    var currentImage = null;
                    structureModel._imageList.forEach(function (image) {
                        if ('/' + image.href === href || image.href === href) {
                            currentImage = image;
                        }
                    });
                    if (currentImage != null) {
                        this.trigger(new components.events.ImageSelectedEvent(this, currentImage));
                    }
                };
                Object.defineProperty(MyCoReStructFileComponent.prototype, "handlesEvents", {
                    get: function () {
                        return [components.events.LanguageModelLoadedEvent.TYPE];
                    },
                    enumerable: true,
                    configurable: true
                });
                return MyCoReStructFileComponent;
            }(components.ViewerComponent));
            components.MyCoReStructFileComponent = MyCoReStructFileComponent;
        })(components = viewer.components || (viewer.components = {}));
    })(viewer = mycore.viewer || (mycore.viewer = {}));
})(mycore || (mycore = {}));
var mycore;
(function (mycore) {
    var viewer;
    (function (viewer) {
        var components;
        (function (components) {
            var MyCoReIIIFComponent = (function (_super) {
                __extends(MyCoReIIIFComponent, _super);
                function MyCoReIIIFComponent(settings, container) {
                    var _this = _super.call(this, settings, container) || this;
                    _this.settings = settings;
                    _this.container = container;
                    _this.structFileAndLanguageSync = Utils.synchronize([
                        function (context) { return context.mm != null; },
                        function (context) { return context.lm != null; }
                    ], function (context) {
                        _this.structFileLoaded(_this.mm.model);
                    });
                    return _this;
                }
                MyCoReIIIFComponent.prototype.init = function () {
                    var _this = this;
                    var settings = this.settings;
                    if (settings.doctype === 'manifest') {
                        this.vStructFileLoaded = false;
                        var tilePathBuilder = function (imageUrl, width, height) {
                            var scaleFactor = _this.getScaleFactor(width, height);
                            return imageUrl + '/full/' + Math.floor(width / scaleFactor) + ','
                                + Math.floor(height / scaleFactor) + '/0/default.jpg';
                        };
                        var manifestPromise = mycore.viewer.widgets.iiif.IviewIIIFProvider
                            .loadModel(this.settings.manifestURL, this.settings.imageAPIURL, tilePathBuilder);
                        manifestPromise.then(function (resolved) {
                            var model = resolved.model;
                            _this.trigger(new components.events.WaitForEvent(_this, components.events.LanguageModelLoadedEvent.TYPE));
                            if (model === null) {
                                _this.error = true;
                                _this.errorSync(_this);
                                return;
                            }
                            _this.mm = resolved;
                            _this.structFileAndLanguageSync(_this);
                        });
                        manifestPromise.onreject(function () {
                            _this.trigger(new components.events.WaitForEvent(_this, components.events.LanguageModelLoadedEvent.TYPE));
                            _this.error = true;
                            _this.errorSync(_this);
                        });
                        this.trigger(new components.events.ComponentInitializedEvent(this));
                    }
                };
                MyCoReIIIFComponent.prototype.handle = function (e) {
                    if (e.type === components.events.LanguageModelLoadedEvent.TYPE) {
                        var languageModelLoadedEvent = e;
                        this.lm = languageModelLoadedEvent.languageModel;
                        this.errorSync(this);
                        this.structFileAndLanguageSync(this);
                    }
                    return;
                };
                MyCoReIIIFComponent.prototype.getScaleFactor = function (width, height) {
                    var largestScaling = Math.min(256 / width, 256 / height);
                    return Math.pow(2, Math.ceil(Math.log(largestScaling) / Math.log(1 / 2)));
                };
                return MyCoReIIIFComponent;
            }(components.MyCoReStructFileComponent));
            components.MyCoReIIIFComponent = MyCoReIIIFComponent;
        })(components = viewer.components || (viewer.components = {}));
    })(viewer = mycore.viewer || (mycore.viewer = {}));
})(mycore || (mycore = {}));
addViewerComponent(mycore.viewer.components.MyCoReIIIFComponent);
var mycore;
(function (mycore) {
    var viewer;
    (function (viewer) {
        var widgets;
        (function (widgets) {
            var image;
            (function (image) {
                var IIIFImageInformation = (function () {
                    function IIIFImageInformation(vPath, vTiles, vWidth, vHeight, vZoomlevel) {
                        this.vPath = vPath;
                        this.vTiles = vTiles;
                        this.vWidth = vWidth;
                        this.vHeight = vHeight;
                        this.vZoomlevel = vZoomlevel;
                    }
                    Object.defineProperty(IIIFImageInformation.prototype, "path", {
                        get: function () {
                            return this.vPath;
                        },
                        enumerable: true,
                        configurable: true
                    });
                    Object.defineProperty(IIIFImageInformation.prototype, "tiles", {
                        get: function () {
                            return this.vTiles;
                        },
                        enumerable: true,
                        configurable: true
                    });
                    Object.defineProperty(IIIFImageInformation.prototype, "width", {
                        get: function () {
                            return this.vWidth;
                        },
                        enumerable: true,
                        configurable: true
                    });
                    Object.defineProperty(IIIFImageInformation.prototype, "height", {
                        get: function () {
                            return this.vHeight;
                        },
                        enumerable: true,
                        configurable: true
                    });
                    Object.defineProperty(IIIFImageInformation.prototype, "zoomlevel", {
                        get: function () {
                            return this.vZoomlevel;
                        },
                        enumerable: true,
                        configurable: true
                    });
                    return IIIFImageInformation;
                }());
                image.IIIFImageInformation = IIIFImageInformation;
                var IIIFImageInformationProvider = (function () {
                    function IIIFImageInformationProvider() {
                    }
                    IIIFImageInformationProvider.getInformation = function (href, callback, errorCallback) {
                        if (errorCallback === void 0) { errorCallback = function (err) {
                            return;
                        }; }
                        var settings = {
                            url: href + '/info.json',
                            async: true,
                            success: function (response) {
                                var imageInformation = IIIFImageInformationProvider.proccessJSON(response, href);
                                callback(imageInformation);
                            },
                            error: function (request, status, exception) {
                                errorCallback(exception);
                            }
                        };
                        jQuery.ajax(settings);
                    };
                    IIIFImageInformationProvider.proccessJSON = function (node, path) {
                        var zommLevels = node.tiles[0].scaleFactors;
                        var width = node.width;
                        var height = node.height;
                        var tiles = 0;
                        for (var i = 0; i < zommLevels.length + 1; i++) {
                            tiles = tiles + (Math.ceil(width / (256 * Math.pow(2, i))) * Math.ceil(height / (256 * Math.pow(2, i))));
                        }
                        return new IIIFImageInformation(path, tiles, width, height, zommLevels.length);
                    };
                    return IIIFImageInformationProvider;
                }());
                image.IIIFImageInformationProvider = IIIFImageInformationProvider;
            })(image = widgets.image || (widgets.image = {}));
        })(widgets = viewer.widgets || (viewer.widgets = {}));
    })(viewer = mycore.viewer || (mycore.viewer = {}));
})(mycore || (mycore = {}));
var mycore;
(function (mycore) {
    var viewer;
    (function (viewer) {
        var widgets;
        (function (widgets) {
            var canvas;
            (function (canvas) {
                var TileImagePage = (function () {
                    function TileImagePage(id, width, height, tilePaths) {
                        this.id = id;
                        this.width = width;
                        this.height = height;
                        this.vTiles = new MyCoReMap();
                        this.vLoadingTiles = new MyCoReMap();
                        this.vBackBuffer = document.createElement('canvas');
                        this.vBackBufferArea = null;
                        this.vBackBufferAreaZoom = null;
                        this.vPreviewBackBuffer = document.createElement('canvas');
                        this.vPreviewBackBufferArea = null;
                        this.vPreviewBackBufferAreaZoom = null;
                        this.vImgPreviewLoaded = false;
                        this.vImgNotPreviewLoaded = false;
                        this.htmlContent = new ViewerProperty(this, 'htmlContent');
                        this.vTilePath = tilePaths;
                        this.loadTile(new Position3D(0, 0, 0));
                    }
                    Object.defineProperty(TileImagePage.prototype, "size", {
                        get: function () {
                            return new Size2D(this.width, this.height);
                        },
                        enumerable: true,
                        configurable: true
                    });
                    TileImagePage.prototype.draw = function (ctx, rect, scale, overview) {
                        if (rect.pos.x < 0 || rect.pos.y < 0) {
                            rect = new Rect(rect.pos.max(0, 0), rect.size);
                        }
                        var zoomLevel = Math.min(this.getZoomLevel(scale), this.maxZoomLevel());
                        var zoomLevelScale = this.scaleForLevel(zoomLevel);
                        var diff = scale / zoomLevelScale;
                        var tileSizeInZoomLevel = TileImagePage.TILE_SIZE / zoomLevelScale;
                        var startX = Math.floor(rect.pos.x / tileSizeInZoomLevel);
                        var startY = Math.floor(rect.pos.y / tileSizeInZoomLevel);
                        var endX = Math.ceil(Math.min(rect.pos.x + rect.size.width, this.size.width) / tileSizeInZoomLevel);
                        var endY = Math.ceil(Math.min(rect.pos.y + rect.size.height, this.size.height) / tileSizeInZoomLevel);
                        this.updateBackbuffer(startX, startY, endX, endY, zoomLevel, overview);
                        ctx.save();
                        {
                            var xBase = (startX * tileSizeInZoomLevel - rect.pos.x) * scale;
                            var yBase = (startY * tileSizeInZoomLevel - rect.pos.y) * scale;
                            ctx.translate(xBase, yBase);
                            ctx.scale(diff, diff);
                            if (overview) {
                                ctx.drawImage(this.vPreviewBackBuffer, 0, 0);
                            }
                            else {
                                ctx.drawImage(this.vBackBuffer, 0, 0);
                            }
                        }
                        ctx.restore();
                    };
                    TileImagePage.prototype.getHTMLContent = function () {
                        return this.htmlContent;
                    };
                    TileImagePage.prototype.updateHTML = function () {
                        if (typeof this.refreshCallback !== 'undefined' && this.refreshCallback !== null) {
                            this.refreshCallback();
                        }
                    };
                    TileImagePage.prototype.clear = function () {
                        this.abortLoadingTiles();
                        this.vBackBuffer.width = 1;
                        this.vBackBuffer.height = 1;
                        this.vBackBufferAreaZoom = null;
                        var tile = null;
                        var previewTilePos = new Position3D(0, 0, 0);
                        var hasPreview = this.vTiles.has(previewTilePos);
                        if (hasPreview) {
                            tile = this.vTiles.get(previewTilePos);
                        }
                        this.vTiles.clear();
                        if (hasPreview) {
                            this.vTiles.set(previewTilePos, tile);
                        }
                        this.vLoadingTiles.clear();
                    };
                    TileImagePage.prototype.updateBackbuffer = function (startX, startY, endX, endY, zoomLevel, overview) {
                        var newBackBuffer = new Rect(new Position2D(startX, startY), new Size2D(endX - startX, endY - startY));
                        if (overview) {
                            if (this.vPreviewBackBufferArea !== null
                                && !this.vImgPreviewLoaded
                                && this.vPreviewBackBufferArea.equals(newBackBuffer)
                                && zoomLevel === this.vPreviewBackBufferAreaZoom) {
                                return;
                            }
                            else {
                                this.vPreviewBackBuffer.width = newBackBuffer.size.width * 256;
                                this.vPreviewBackBuffer.height = newBackBuffer.size.height * 256;
                                this.drawToBackbuffer(startX, startY, endX, endY, zoomLevel, true);
                            }
                            this.vPreviewBackBufferArea = newBackBuffer;
                            this.vPreviewBackBufferAreaZoom = zoomLevel;
                            this.vImgPreviewLoaded = false;
                        }
                        else {
                            if (this.vBackBufferArea !== null
                                && !this.vImgNotPreviewLoaded
                                && this.vBackBufferArea.equals(newBackBuffer)
                                && zoomLevel === this.vBackBufferAreaZoom) {
                                return;
                            }
                            else {
                                this.vBackBuffer.width = newBackBuffer.size.width * 256;
                                this.vBackBuffer.height = newBackBuffer.size.height * 256;
                                this.drawToBackbuffer(startX, startY, endX, endY, zoomLevel, false);
                            }
                            this.vBackBufferArea = newBackBuffer;
                            this.vBackBufferAreaZoom = zoomLevel;
                            this.vImgNotPreviewLoaded = false;
                        }
                    };
                    TileImagePage.prototype.abortLoadingTiles = function () {
                        this.vLoadingTiles.forEach(function (k, v) {
                            delete v.src;
                            delete v.onload;
                            delete v.onerror;
                        });
                        this.vLoadingTiles.clear();
                    };
                    TileImagePage.prototype.drawToBackbuffer = function (startX, startY, endX, endY, zoomLevel, overview) {
                        var ctx;
                        if (overview) {
                            ctx = this.vPreviewBackBuffer.getContext('2d');
                        }
                        else {
                            ctx = this.vBackBuffer.getContext('2d');
                        }
                        for (var x = startX; x < endX; x++) {
                            for (var y = startY; y < endY; y++) {
                                var tilePosition = new Position3D(x, y, zoomLevel);
                                var tile = this.loadTile(tilePosition);
                                var rasterPositionX = (x - startX) * 256;
                                var rasterPositionY = (y - startY) * 256;
                                if (tile !== null) {
                                    ctx.drawImage(tile, Math.floor(rasterPositionX), rasterPositionY, tile.naturalWidth, tile.naturalHeight);
                                }
                                else {
                                    var preview = this.getPreview(tilePosition);
                                    if (preview !== null) {
                                        this.drawPreview(ctx, new Position2D(rasterPositionX, rasterPositionY), preview);
                                    }
                                }
                            }
                        }
                    };
                    TileImagePage.prototype.drawPreview = function (ctx, targetPosition, tile) {
                        tile.areaToDraw.size.width = Math.min(tile.areaToDraw.pos.x + tile.areaToDraw.size.width, tile.tile.naturalWidth) - tile.areaToDraw.pos.x;
                        tile.areaToDraw.size.height = Math.min(tile.areaToDraw.pos.y + tile.areaToDraw.size.height, tile.tile.naturalHeight) - tile.areaToDraw.pos.y;
                        ctx.drawImage(tile.tile, tile.areaToDraw.pos.x, tile.areaToDraw.pos.y, tile.areaToDraw.size.width, tile.areaToDraw.size.height, targetPosition.x, targetPosition.y, tile.areaToDraw.size.width * tile.scale, tile.areaToDraw.size.height * tile.scale);
                    };
                    TileImagePage.prototype.loadTile = function (tilePos) {
                        var _this = this;
                        if (this.vTiles.has(tilePos)) {
                            return this.vTiles.get(tilePos);
                        }
                        else {
                            if (!this.vLoadingTiles.has(tilePos)) {
                                this._loadTile(tilePos, function (img) {
                                    _this.vTiles.set(tilePos, img);
                                    if (typeof _this.refreshCallback !== 'undefined' && _this.refreshCallback !== null) {
                                        _this.vImgPreviewLoaded = true;
                                        _this.vImgNotPreviewLoaded = true;
                                        _this.refreshCallback();
                                    }
                                }, function () {
                                    console.error('Could not load tile : ' + tilePos.toString());
                                });
                            }
                        }
                        return null;
                    };
                    TileImagePage.prototype.getPreview = function (tilePos, scale) {
                        if (scale === void 0) { scale = 1; }
                        if (this.vTiles.has(tilePos)) {
                            var tile = this.vTiles.get(tilePos);
                            return { tile: tile, areaToDraw: new Rect(new Position2D(0, 0), new Size2D(256, 256)), scale: scale };
                        }
                        else {
                            var newZoom = tilePos.z - 1;
                            if (newZoom < 0) {
                                return null;
                            }
                            var newPos = new Position2D(Math.floor(tilePos.x / 2), Math.floor(tilePos.y / 2));
                            var xGridPos = tilePos.x % 2;
                            var yGridPos = tilePos.y % 2;
                            var prev = this.getPreview(new Position3D(newPos.x, newPos.y, newZoom), scale * 2);
                            if (prev !== null) {
                                var newAreaSize = new Size2D(prev.areaToDraw.size.width / 2, prev.areaToDraw.size.height / 2);
                                var newAreaPos = new Position2D(prev.areaToDraw.pos.x + (newAreaSize.width * xGridPos), prev.areaToDraw.pos.y + (newAreaSize.height * yGridPos));
                                return {
                                    tile: prev.tile,
                                    areaToDraw: new Rect(newAreaPos, newAreaSize),
                                    scale: prev.scale
                                };
                            }
                            else {
                                return null;
                            }
                        }
                    };
                    TileImagePage.prototype.maxZoomLevel = function () {
                        return Math.max(Math.ceil(Math.log(Math.max(this.width, this.height) / TileImagePage.TILE_SIZE) / Math.LN2), 0);
                    };
                    TileImagePage.prototype.getZoomLevel = function (scale) {
                        return Math.max(0, Math.ceil(this.maxZoomLevel() - Math.log(scale) / Utils.LOG_HALF));
                    };
                    TileImagePage.prototype.scaleForLevel = function (level) {
                        return Math.pow(0.5, this.maxZoomLevel() - level);
                    };
                    TileImagePage.prototype._loadTile = function (tilePos, okCallback, errorCallback) {
                        var _this = this;
                        var pathSelect = Utils.hash(tilePos.toString()) % this.vTilePath.length;
                        var path = this.vTilePath[pathSelect];
                        var image = new Image();
                        image.onload = function () {
                            _this.vLoadingTiles.remove(tilePos);
                            okCallback(image);
                        };
                        image.onerror = function () {
                            errorCallback();
                        };
                        image.src = ViewerFormatString(path, tilePos);
                        this.vLoadingTiles.set(tilePos, image);
                    };
                    TileImagePage.prototype.toString = function () {
                        return this.vTilePath[0];
                    };
                    TileImagePage.TILE_SIZE = 256;
                    return TileImagePage;
                }());
                canvas.TileImagePage = TileImagePage;
            })(canvas = widgets.canvas || (widgets.canvas = {}));
        })(widgets = viewer.widgets || (viewer.widgets = {}));
    })(viewer = mycore.viewer || (mycore.viewer = {}));
})(mycore || (mycore = {}));
var mycore;
(function (mycore) {
    var viewer;
    (function (viewer) {
        var widgets;
        (function (widgets) {
            var canvas;
            (function (canvas) {
                var TileImagePageIIIF = (function (_super) {
                    __extends(TileImagePageIIIF, _super);
                    function TileImagePageIIIF() {
                        return _super !== null && _super.apply(this, arguments) || this;
                    }
                    TileImagePageIIIF.prototype.loadTile = function (tilePos) {
                        var _this = this;
                        var iiifPos = this.tilePosToIIIFPos(tilePos);
                        if (this.vTiles.has(tilePos)) {
                            return this.vTiles.get(tilePos);
                        }
                        else {
                            if (!this.vLoadingTiles.has(tilePos)) {
                                this._loadTileIIIF(tilePos, iiifPos, function (img) {
                                    _this.vTiles.set(tilePos, img);
                                    if (typeof _this.refreshCallback !== 'undefined' && _this.refreshCallback !== null) {
                                        _this.vImgPreviewLoaded = true;
                                        _this.vImgNotPreviewLoaded = true;
                                        _this.refreshCallback();
                                    }
                                }, function () {
                                    console.error('Could not load tile : ' + tilePos.toString());
                                });
                            }
                        }
                        return null;
                    };
                    TileImagePageIIIF.prototype.tilePosToIIIFPos = function (tilePos) {
                        var iiifPos;
                        iiifPos = tilePos;
                        iiifPos.x = iiifPos.x * 256 * Math.pow(2, this.maxZoomLevel() - iiifPos.z);
                        iiifPos.w = 256 * Math.pow(2, this.maxZoomLevel() - iiifPos.z);
                        iiifPos.y = iiifPos.y * 256 * Math.pow(2, this.maxZoomLevel() - iiifPos.z);
                        iiifPos.h = 256 * Math.pow(2, this.maxZoomLevel() - iiifPos.z);
                        iiifPos.tx = ((iiifPos.x + iiifPos.w) > this.width) ? Math.ceil((this.width - iiifPos.x)
                            / Math.pow(2, this.maxZoomLevel() - iiifPos.z)) : 256;
                        iiifPos.ty = ((iiifPos.y + iiifPos.h) > this.height) ? Math.ceil((this.height - iiifPos.y)
                            / Math.pow(2, this.maxZoomLevel() - iiifPos.z)) : 256;
                        return iiifPos;
                    };
                    TileImagePageIIIF.prototype._loadTileIIIF = function (tilePos, iiifPos, okCallback, errorCallback) {
                        var _this = this;
                        var pathSelect = Utils.hash(tilePos.toString()) % this.vTilePath.length;
                        var path = this.vTilePath[pathSelect];
                        var image = new Image();
                        image.onload = function () {
                            _this.vLoadingTiles.remove(tilePos);
                            okCallback(image);
                        };
                        image.onerror = function () {
                            errorCallback();
                        };
                        image.src = ViewerFormatString(path, iiifPos);
                        this.vLoadingTiles.set(tilePos, image);
                    };
                    return TileImagePageIIIF;
                }(canvas.TileImagePage));
                canvas.TileImagePageIIIF = TileImagePageIIIF;
            })(canvas = widgets.canvas || (widgets.canvas = {}));
        })(widgets = viewer.widgets || (viewer.widgets = {}));
    })(viewer = mycore.viewer || (mycore.viewer = {}));
})(mycore || (mycore = {}));
var mycore;
(function (mycore) {
    var viewer;
    (function (viewer) {
        var components;
        (function (components) {
            var PageLoadedEvent = mycore.viewer.components.events.PageLoadedEvent;
            var MyCoReIIIFPageProviderComponent = (function (_super) {
                __extends(MyCoReIIIFPageProviderComponent, _super);
                function MyCoReIIIFPageProviderComponent(settings) {
                    var _this = _super.call(this) || this;
                    _this.settings = settings;
                    _this.vImageInformationMap = new MyCoReMap();
                    _this.vImagePageMap = new MyCoReMap();
                    _this.vImageHTMLMap = new MyCoReMap();
                    _this.vImageCallbackMap = new MyCoReMap();
                    return _this;
                }
                MyCoReIIIFPageProviderComponent.prototype.init = function () {
                    if (this.settings.doctype === 'manifest') {
                        this.trigger(new components.events.WaitForEvent(this, components.events.RequestPageEvent.TYPE));
                    }
                };
                MyCoReIIIFPageProviderComponent.prototype.getPage = function (image, resolve) {
                    var _this = this;
                    if (this.vImagePageMap.has(image)) {
                        resolve(this.vImagePageMap.get(image));
                    }
                    else {
                        if (this.vImageCallbackMap.has(image)) {
                            this.vImageCallbackMap.get(image).push(resolve);
                        }
                        else {
                            var initialArray = [];
                            initialArray.push(resolve);
                            this.vImageCallbackMap.set(image, initialArray);
                            this.getPageMetadata(image, function (metadata) {
                                var imagePage = _this.createPageFromMetadata(image, metadata);
                                var resolveList = _this.vImageCallbackMap.get(image);
                                var pop;
                                while (pop = resolveList.pop()) {
                                    pop(imagePage);
                                }
                                _this.vImagePageMap.set(image, imagePage);
                                _this.trigger(new PageLoadedEvent(_this, image, imagePage));
                            });
                        }
                    }
                };
                MyCoReIIIFPageProviderComponent.prototype.createPageFromMetadata = function (imageId, metadata) {
                    var paths = [];
                    paths.push(metadata.path + '/{x},{y},{w},{h}/!{tx},{ty}/0/default.jpg');
                    return new viewer.widgets.canvas.TileImagePageIIIF(imageId, metadata.width, metadata.height, paths);
                };
                MyCoReIIIFPageProviderComponent.prototype.getPageMetadata = function (image, resolve) {
                    var _this = this;
                    image = (image.charAt(0) === '/') ? image.substr(1) : image;
                    if (this.vImageInformationMap.has(image)) {
                        resolve(this.vImageInformationMap.get(image));
                    }
                    else {
                        var path = this.settings.imageAPIURL + image;
                        mycore.viewer.widgets.image.IIIFImageInformationProvider.getInformation(path, function (info) {
                            _this.vImageInformationMap.set(image, info);
                            resolve(info);
                        }, function (error) {
                            console.log('Error while loading ImageInformations', +error.toString());
                        });
                    }
                };
                Object.defineProperty(MyCoReIIIFPageProviderComponent.prototype, "handlesEvents", {
                    get: function () {
                        if (this.settings.doctype === 'manifest') {
                            return [components.events.RequestPageEvent.TYPE];
                        }
                        else {
                            return [];
                        }
                    },
                    enumerable: true,
                    configurable: true
                });
                MyCoReIIIFPageProviderComponent.prototype.handle = function (e) {
                    if (e.type === components.events.RequestPageEvent.TYPE) {
                        var rpe_1 = e;
                        this.getPage(rpe_1._pageId, function (page) {
                            rpe_1._onResolve(rpe_1._pageId, page);
                        });
                    }
                    return;
                };
                return MyCoReIIIFPageProviderComponent;
            }(components.ViewerComponent));
            components.MyCoReIIIFPageProviderComponent = MyCoReIIIFPageProviderComponent;
        })(components = viewer.components || (viewer.components = {}));
    })(viewer = mycore.viewer || (mycore.viewer = {}));
})(mycore || (mycore = {}));
addViewerComponent(mycore.viewer.components.MyCoReIIIFPageProviderComponent);
console.log('IIIF MODULE');
