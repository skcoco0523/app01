/**
 * エアコンリモコン専用クラス
 */
class AirconRemote {
    constructor(baseRemote, initialSettings) {
        this.base = baseRemote;
        this.settings = this.parseSettings(initialSettings);
        this.init();
    }

    /**
     * 設定値をパースして型を保証する
     */
    parseSettings(s) {
        const defaults = {
            power: true,
            temp: 25.0,
            mode: 'cool',
            fan: 'auto',
            swingv: 'auto',
            clean: false
        };

        if (!s || typeof s !== 'object') return defaults;

        return {
            power: s.power === undefined ? defaults.power : (s.power === 'true' || s.power === true || s.power === 1 || s.power === "1"),
            temp:  s.temp !== undefined ? parseFloat(s.temp) : defaults.temp,
            mode:  s.mode || defaults.mode,
            fan:   s.fan || defaults.fan,
            swingv: s.swingv || defaults.swingv,
            clean: s.clean === undefined ? defaults.clean : (s.clean === 'true' || s.clean === true || s.clean === 1 || s.clean === "1")
        };
    }

    init() {
        this.updateDisplay();
        this.bindEvents();
    }

    /**
     * 表示更新
     */
    updateDisplay() {
        const lcdTemp = document.getElementById('lcd-temp');
        const lcdMode = document.getElementById('lcd-mode');
        const lcdFan  = document.getElementById('lcd-fan');
        const lcdSwing = document.getElementById('lcd-swing');
        const lcdClean = document.getElementById('lcd-clean');
        const lcdPowerOff = document.getElementById('lcd-power-off');
        const lcdMainDisplay = document.getElementById('lcd-main-display');

        if (!lcdTemp) return;

        // 温度 (0.5単位)
        lcdTemp.textContent = this.settings.temp.toFixed(1);

        // モード
        const modeLabels = { 'cool': '冷房', 'heat': '暖房', 'dry': '除湿', 'fan': '送風', 'auto': '自動' };
        lcdMode.textContent = modeLabels[this.settings.mode] || this.settings.mode;

        // 風量
        const fanLabels = { 'auto': '風量 自動', 'min': '風量 ■', 'low': '風量 ■■', 'medium': '風量 ■■■', 'high': '風量 ■■■■', 'max': '風量 ■■■■■' };
        lcdFan.textContent = fanLabels[this.settings.fan] || `風量 ${this.settings.fan}`;

        // 風向き
        const swingLabels = { 'auto': '風向 自動', 'highest': '風向 最高', 'high': '風向 高', 'middle': '風向 中', 'low': '風向 低', 'lowest': '風向 最低' };
        if (lcdSwing) lcdSwing.textContent = swingLabels[this.settings.swingv] || '風向 自動';

        // 内部クリーン
        if (lcdClean) {
            lcdClean.textContent = '内部ｸﾘｰﾝ';
            lcdClean.style.display = this.settings.clean ? 'inline' : 'none';
        }

        // 電源
        if (this.settings.power) {
            lcdPowerOff.style.display = 'none';
            lcdMainDisplay.style.setProperty('display', 'flex', 'important');
        } else {
            lcdPowerOff.style.display = 'block';
            lcdMainDisplay.style.setProperty('display', 'none', 'important');
        }
    }

    /**
     * イベント紐付け
     */
    bindEvents() {
        const container = document.getElementById('RemoteDesignContainer');
        if (!container) return;

        container.querySelectorAll('button[data-lib-protocol]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (this.base.isEditingMode) return;

                const protocol = btn.dataset.libProtocol;
                const action   = btn.dataset.action;
                const value    = btn.dataset.value;

                if (action) {
                    this.handleAction(action, value);
                    this.updateDisplay();
                    this.base.sendLibrary(protocol, null, null, this.settings);
                }
            });
        });
    }

    /**
     * アクション処理
     */
    handleAction(action, value) {
        switch(action) {
            case 'power-on':
                this.settings.power = true;
                break;
            case 'power-off':
                this.settings.power = false;
                break;
            case 'temp-up':
                if (this.settings.temp < 30) this.settings.temp = parseFloat((this.settings.temp + 0.5).toFixed(1));
                this.settings.power = true;
                break;
            case 'temp-down':
                if (this.settings.temp > 16) this.settings.temp = parseFloat((this.settings.temp - 0.5).toFixed(1));
                this.settings.power = true;
                break;
            case 'mode-change':
                this.settings.mode = value;
                this.settings.power = true;
                break;
            case 'fan-change':
                const fans = ['auto', 'min', 'low', 'medium', 'high', 'max'];
                let fanIdx = fans.indexOf(this.settings.fan);
                this.settings.fan = fans[(fanIdx + 1) % fans.length];
                this.settings.power = true;
                break;
            case 'swing-change':
                const swings = ['auto', 'highest', 'high', 'middle', 'low', 'lowest'];
                let swingIdx = swings.indexOf(this.settings.swingv);
                if (swingIdx === -1) swingIdx = 0;
                this.settings.swingv = swings[(swingIdx + 1) % swings.length];
                this.settings.power = true;
                break;
            case 'clean-toggle':
                this.settings.clean = !this.settings.clean;
                break;
            case 'quiet-toggle':
                // 「しずか」は風量の一つとして扱うか、フラグにするか。
                // 今回は風量を 'min' に切り替える挙動とする
                this.settings.fan = (this.settings.fan === 'min') ? 'auto' : 'min';
                this.settings.power = true;
                break;
        }
    }
}
