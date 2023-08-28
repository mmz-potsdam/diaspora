/// <reference path="../../../../../../../src/main/typescript/modules/pdf/definitions/pdf.d.ts" />
declare namespace mycore.viewer.components {
    interface PDFSettings extends MyCoReViewerSettings {
        pdfProviderURL: string;
        pdfWorkerURL: string;
    }
}
declare namespace mycore.viewer.widgets.pdf {
    class PDFStructureModel extends model.StructureModel {
        refPageMap: MyCoReMap<string, PDFPageProxy>;
        constructor(_rootChapter: mycore.viewer.model.StructureChapter, _imageList: Array<model.StructureImage>, _chapterToImageMap: MyCoReMap<string, model.StructureImage>, _imageToChapterMap: MyCoReMap<string, model.StructureChapter>, _imageHrefImageMap: MyCoReMap<string, model.StructureImage>, refPageMap: MyCoReMap<string, PDFPageProxy>);
        startPage?: number;
    }
}
declare namespace mycore.viewer.widgets.pdf {
    class PDFStructureBuilder {
        private _document;
        private _name;
        private _startPage;
        constructor(_document: PDFDocumentProxy, _name: string);
        private _structureModel;
        private _chapterPageMap;
        private _pages;
        private _pageCount;
        private _refPageMap;
        private _idPageMap;
        private _loadedPageCount;
        private _outline;
        private _rootChapter;
        private _promise;
        private _outlineTodoCount;
        private static PDF_TEXT_HREF;
        resolve(): GivenViewerPromise<PDFStructureModel, any>;
        private _resolveStartPage;
        private _resolvePages;
        private _createThumbnailDrawer;
        private _renderPage;
        private _resolveOutline;
        getPageNumberFromDestination(dest: String, callback: (number: number) => void): void;
        private getChapterFromOutline;
        private checkResolvable;
        private resolveStructure;
        private static destToString;
    }
}
declare namespace mycore.viewer.widgets.canvas {
    class PDFPage implements model.AbstractPage {
        id: string;
        private pdfPage;
        private builder;
        constructor(id: string, pdfPage: PDFPageProxy, builder: pdf.PDFStructureBuilder);
        static CSS_UNITS: number;
        refreshCallback: () => void;
        size: Size2D;
        private _rotation;
        private _frontBuffer;
        private _backBuffer;
        private _timeOutIDHolder;
        private _bbScale;
        private _fbScale;
        private _promiseRunning;
        private _textData;
        resolveTextContent(callback: (content: model.TextContentModel) => void): void;
        private getRectFromAnnotation;
        draw(ctx: CanvasRenderingContext2D, rect: Rect, sourceScale: any, overview: boolean, infoScale: number): void;
        private _updateBackBuffer;
        private _swapBuffers;
        toString(): string;
        clear(): void;
    }
}
declare namespace mycore.viewer.components {
    class MyCoRePDFViewerComponent extends ViewerComponent {
        private _settings;
        private container;
        constructor(_settings: PDFSettings, container: JQuery);
        private _structureBuilder;
        private _pdfDocument;
        private _pageCount;
        private _structure;
        private _structureModelLoadedEvent;
        private _pageCache;
        private _pdfUrl;
        private _errorModalSynchronize;
        private error;
        private toolbarLanguageSync;
        private _toolbarModel;
        private _languageModel;
        init(): void;
        handle(e: mycore.viewer.widgets.events.ViewerEvent): void;
        readonly handlesEvents: string[];
        private addDownloadButton;
    }
}
