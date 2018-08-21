import EzConfigBase from './base';

export default class EzCustomStyleConfig extends EzConfigBase {
    constructor(customStyles = []) {
        super();

        this.name = 'custom-style';
        this.buttons = [
            'ezmoveup',
            'ezmovedown',
            this.getStyles(customStyles),
            'ezblocktextalignleft',
            'ezblocktextaligncenter',
            'ezblocktextalignright',
            'ezblocktextalignjustify',
            'ezblockremove',
        ];
    }

    getStyles(customStyles = []) {
        return {
            name: 'styles',
            cfg: {
                showRemoveStylesItem: false,
                styles: [...customStyles],
            },
        };
    }

    /**
     * Tests whether the `paragraph` toolbar should be visible. It is
     * visible when the selection is empty and when the caret is inside a
     * paragraph.
     *
     * @method test
     * @param {Object} payload
     * @param {AlloyEditor.Core} payload.editor
     * @param {Object} payload.data
     * @param {Object} payload.data.selectionData
     * @param {Event} payload.data.nativeEvent
     * @return {Boolean}
     */
    test(payload) {
        const nativeEditor = payload.editor.get('nativeEditor');
        const path = nativeEditor.elementPath();

        return (
            nativeEditor.isSelectionEmpty() &&
            path &&
            path.contains(function(el) {
                return el.hasAttribute('data-style');
            })
        );
    }
}
