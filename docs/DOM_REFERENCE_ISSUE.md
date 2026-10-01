# DOM要素の参照ズレとUI未更新対策 (DOM_REFERENCE_ISSUE.md)

## 1. 概要
本ドキュメントは、JavaScriptによるDOM操作において「ログでは処理が成功している」「コンソールからの手動実行では動く」にもかかわらず、実際の画面上のUI（パネルの表示切替やキャンバス描画など）が更新されない問題の原因と解決策を定義します。
根本原因は、JSの変数が画面から切断された旧要素を保持し続ける「参照ズレ（離脱したDOM要素の操作）」にあります。

---

## 2. 現象と発生メカニズム

### 現象
| 実行環境 | 挙動 | 補足 |
| :--- | :--- | :--- |
| **JSスクリプト内** | 画面が更新されない | `element.classList.remove('d-none')` などを実行しても見た目が変わらない。 |
| **DevToolsコンソール** | 画面が更新される | `document.getElementById(...)` で直接操作すると即座に反映される。 |

### 発生メカニズム
1. **初期取得**: スクリプトの最上部などで `const element = document.getElementById('id')` を実行し、DOM要素を変数に保持する。
2. **DOMの破壊と再構築**: 別の処理（親要素の `innerHTML` の書き換えやUIコンポーネントの再描画など）によって、画面上の該当要素が一旦削除され、新しい要素として再生成される。
3. **参照ズレの発生**: JavaScriptの変数は**メモリ上に残された古いDOM（画面から切断済みの要素）**を指し続けているため、いくら操作しても画面上の新しいDOMには反映されない。

---

## 3. 検証・診断方法 (JavaScript)

操作対象のDOMが最新の状態かを確認するため、該当する関数（描画ロジックなど）の直前で以下のログを出力します。

```javascript
// ① 変数が指しているDOMが現在の画面（Document）に接続されているか確認
console.log('isConnected:', myElement.isConnected); 
// 👉 false の場合は参照ズレが発生しています

// ② 最新のDOM検索結果と一致するか確認
console.log('Match with current DOM:', myElement === document.getElementById('my-element-id')); 
// 👉 false の場合は参照ズレが発生しています