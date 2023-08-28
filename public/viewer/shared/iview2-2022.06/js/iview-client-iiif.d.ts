/// <reference path="../../../../../../../src/main/typescript/modules/base/definitions/manifesto.d.ts" />
declare namespace mycore.viewer.widgets.iiif {
    class IIIFStructureModel extends model.StructureModel {
        smLinkMap: MyCoReMap<string, string[]>;
        constructor(smLinkMap: MyCoReMap<string, string[]>, rootChapter: model.StructureChapter, imageList: model.StructureImage[], chapterToImageMap: MyCoReMap<string, model.StructureImage>, imageToChapterMap: MyCoReMap<string, model.StructureChapter>, imageHrefImageMap: MyCoReMap<string, model.StructureImage>);
    }
}
declare namespace mycore.viewer.widgets.iiif {
    import Manifest = Manifesto.Manifest;
    class IIIFStructureBuilder {
        private manifestDocument;
        private tilePathBuilder;
        private imageAPIURL;
        private hrefResolverElement;
        private vSmLinkMap;
        private vChapterIdMap;
        private vIdFileMap;
        private vIdPhysicalFileMap;
        private vChapterImageMap;
        private vImageChapterMap;
        private vManifestChapter;
        private vImageList;
        private vStructureModel;
        private vIdImageMap;
        private vImprovisationMap;
        private vImageHrefImageMap;
        constructor(manifestDocument: Manifest, tilePathBuilder: (href: string, width: number, height: number) => string, imageAPIURL: any);
        processManifest(): model.StructureModel;
        private processChapter;
        private getIdFileMap;
        private processImages;
        private makeLinks;
        private makeLinksWithoutStructures;
        private makeLink;
        private parseFile;
        private getIDFromURL;
        private getHrefFromID;
    }
}
declare namespace mycore.viewer.widgets.iiif {
    class IviewIIIFProvider {
        static loadModel(manifestDocumentLocation: string, imageAPIURL: string, tilePathBuilder: (href: string, width: number, height: number) => string): GivenViewerPromise<{
            model: model.StructureModel;
            document: Document;
        }, any>;
    }
}
declare namespace mycore.viewer.components {
    interface IIIFSettings extends MyCoReViewerSettings {
        manifestURL: string;
        imageAPIURL: string;
        pageRange: number;
    }
}
declare namespace mycore.viewer.components {
    class MyCoReStructFileComponent extends ViewerComponent {
        protected settings: MyCoReViewerSettings;
        protected container: JQuery;
        constructor(settings: MyCoReViewerSettings, container: JQuery);
        protected errorSync: any;
        protected error: boolean;
        protected lm: mycore.viewer.model.LanguageModel;
        protected mm: {
            model: model.StructureModel;
            document: Document;
        };
        protected vStructFileLoaded: boolean;
        protected vEventToTrigger: events.StructureModelLoadedEvent;
        protected postProcessChapter(chapter: model.StructureChapter): void;
        protected buildTranslationKey(type: string): string;
        protected structFileLoaded(structureModel: model.StructureModel): void;
        readonly handlesEvents: string[];
    }
}
declare namespace mycore.viewer.components {
    class MyCoReIIIFComponent extends MyCoReStructFileComponent {
        protected settings: IIIFSettings;
        protected container: JQuery;
        constructor(settings: IIIFSettings, container: JQuery);
        private structFileAndLanguageSync;
        init(): void;
        handle(e: mycore.viewer.widgets.events.ViewerEvent): void;
        private getScaleFactor;
    }
}
declare namespace mycore.viewer.widgets.image {
    class IIIFImageInformation {
        private vPath;
        private vTiles;
        private vWidth;
        private vHeight;
        private vZoomlevel;
        constructor(vPath: string, vTiles: number, vWidth: number, vHeight: number, vZoomlevel: number);
        readonly path: string;
        readonly tiles: number;
        readonly width: number;
        readonly height: number;
        readonly zoomlevel: number;
    }
    class IIIFImageInformationProvider {
        static getInformation(href: string, callback: (info: IIIFImageInformation) => void, errorCallback?: (err: string) => void): void;
        private static proccessJSON;
    }
}
declare namespace mycore.viewer.widgets.canvas {
    class TileImagePage implements model.AbstractPage {
        id: string;
        protected width: number;
        protected height: number;
        constructor(id: string, width: number, height: number, tilePaths: string[]);
        protected static TILE_SIZE: number;
        protected vTilePath: string[];
        protected vTiles: MyCoReMap<Position3D, HTMLImageElement>;
        protected vLoadingTiles: MyCoReMap<Position3D, HTMLImageElement>;
        protected vBackBuffer: HTMLCanvasElement;
        protected vBackBufferArea: Rect;
        protected vBackBufferAreaZoom: number;
        protected vPreviewBackBuffer: HTMLCanvasElement;
        protected vPreviewBackBufferArea: Rect;
        protected vPreviewBackBufferAreaZoom: number;
        protected vImgPreviewLoaded: boolean;
        protected vImgNotPreviewLoaded: boolean;
        protected htmlContent: ViewerProperty<HTMLElement>;
        readonly size: Size2D;
        refreshCallback: () => void;
        draw(ctx: CanvasRenderingContext2D, rect: Rect, scale: number, overview: boolean): void;
        getHTMLContent(): ViewerProperty<HTMLElement>;
        protected updateHTML(): void;
        clear(): void;
        protected updateBackbuffer(startX: number, startY: number, endX: number, endY: number, zoomLevel: number, overview: boolean): void;
        protected abortLoadingTiles(): void;
        protected drawToBackbuffer(startX: number, startY: number, endX: number, endY: number, zoomLevel: number, overview: boolean): void;
        protected drawPreview(ctx: CanvasRenderingContext2D, targetPosition: Position2D, tile: PreviewTile): void;
        protected loadTile(tilePos: Position3D): HTMLImageElement;
        protected getPreview(tilePos: Position3D, scale?: number): PreviewTile;
        protected maxZoomLevel(): number;
        protected getZoomLevel(scale: number): number;
        protected scaleForLevel(level: number): number;
        protected _loadTile(tilePos: Position3D, okCallback: (image: HTMLImageElement) => void, errorCallback: () => void): void;
        toString(): string;
    }
    interface PreviewTile {
        tile: HTMLImageElement;
        areaToDraw: Rect;
        scale: number;
    }
}
declare namespace mycore.viewer.widgets.canvas {
    class TileImagePageIIIF extends canvas.TileImagePage {
        protected loadTile(tilePos: Position3D): HTMLImageElement;
        private tilePosToIIIFPos;
        private _loadTileIIIF;
    }
}
declare namespace mycore.viewer.components {
    class MyCoReIIIFPageProviderComponent extends ViewerComponent {
        private settings;
        constructor(settings: IIIFSettings);
        init(): void;
        private vImageInformationMap;
        private vImagePageMap;
        private vImageHTMLMap;
        private vImageCallbackMap;
        private getPage;
        private createPageFromMetadata;
        private getPageMetadata;
        readonly handlesEvents: string[];
        handle(e: mycore.viewer.widgets.events.ViewerEvent): void;
    }
}
