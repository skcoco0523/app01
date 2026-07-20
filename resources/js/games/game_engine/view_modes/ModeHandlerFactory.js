export class ModeHandlerFactory {
    /**
     * view_modeに応じたハンドラーを生成
     * @param {string} viewMode 
     * @param {string} gameKey 
     * @returns {Promise<BaseModeHandler>}
     */
    static async create(viewMode, gameKey) {
        let module;
        switch (viewMode) {
            case 'top_down':
                module = await import('./TopDownHandler.js');
                return new module.default(gameKey);
            
            case 'vertical_auto_scroll':
                module = await import('./VerticalAutoScrollHandler.js');
                return new module.default(gameKey);
            
            case 'side_view_flip':
            case 'side_view_separate':
            case 'fixed_screen':
            default:
                // 基本の横スクロール
                module = await import('./BaseModeHandler.js');
                return new module.default(gameKey);
        }
    }
}
