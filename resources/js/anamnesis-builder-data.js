/**
 * Componente Alpine para o builder de campos da anamnese.
 * Mantido em sync com public/js/anamnesis-builder-alpine.js (fallback sem Vite).
 */
export function createAnamnesisBuilderData(initialQuestions, fieldDefaults) {
    return {
        questions: [],
        fieldDefaults: fieldDefaults || {},
        init() {
            const rows = Array.isArray(initialQuestions) && initialQuestions.length
                ? initialQuestions.map((r) => this.normalizeRow(r))
                : [this.emptyRow()];
            this.questions = rows;
        },
        emptyRow() {
            return {
                label: '',
                field_key: '',
                field_type: 'text',
                required: false,
                mask: null,
                validation_rules: [],
                sort_order: 0,
            };
        },
        normalizeRow(r) {
            return {
                label: r.label ?? '',
                field_key: r.field_key ?? '',
                field_type: r.field_type ?? 'text',
                required: Boolean(r.required),
                mask: r.mask ?? null,
                validation_rules: Array.isArray(r.validation_rules) ? [...r.validation_rules] : [],
                sort_order: Number(r.sort_order) || 0,
            };
        },
        addRow() {
            this.questions.push(this.emptyRow());
        },
        removeRow(i) {
            this.questions.splice(i, 1);
            if (this.questions.length === 0) {
                this.questions.push(this.emptyRow());
            }
        },
        onTypeChange(i) {
            const t = this.questions[i].field_type;
            const d = this.fieldDefaults[t];
            if (!d) {
                return;
            }
            this.questions[i].mask = d.mask;
            this.questions[i].validation_rules = [...(d.validation_rules || [])];
        },
    };
}

export default createAnamnesisBuilderData;
