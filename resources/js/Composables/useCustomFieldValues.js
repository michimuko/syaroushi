// カスタムフィールド定義（企画書5-E）から、フォームの初期値オブジェクトを組み立てる。
// 値はcustom_field_definitions.idをキーとしたJSONで保存されるため、キーは常に定義のid。
export function initialCustomFieldValues(definitions, existingValues = {}) {
    const values = {};

    for (const definition of definitions) {
        const existing = existingValues?.[definition.id];
        values[definition.id] =
            existing !== undefined && existing !== null
                ? existing
                : definition.field_type === 'checkbox'
                  ? false
                  : '';
    }

    return values;
}
