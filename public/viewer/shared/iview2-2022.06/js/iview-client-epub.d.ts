declare namespace mycore.viewer.widgets.epub {
    class EpubStructureChapter extends mycore.viewer.model.StructureChapter {
        epubChapter: any;
        constructor(parent: mycore.viewer.model.StructureChapter, type: string, label: string, chapter: mycore.viewer.model.StructureChapter[], epubChapter: any);
    }
}
declare namespace mycore.viewer.widgets.epub {
    class EpubStructureBuilder {
        convertToChapter(item: any, parent?: EpubStructureChapter): EpubStructureChapter;
    }
}
declare namespace mycore.viewer.components {
    class MyCoReEpubDisplayComponent extends ViewerComponent {
        private epubSettings;
        private appContainer;
        private rendition;
        private zoomInPercent;
        private currentHref;
        constructor(epubSettings: MyCoReViewerSettings, appContainer: JQuery);
        readonly handlesEvents: any[];
        handle(e: mycore.viewer.widgets.events.ViewerEvent): void;
        init(): void;
        private handleRequestStateEvent;
        private handleButtonPressedEvent;
        private handleProvideToolbarModelEvent;
        private handleChapterChangedEvent;
    }
}
