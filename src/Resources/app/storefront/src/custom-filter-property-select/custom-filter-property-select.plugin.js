import FilterPropertySelectPlugin from 'src/plugin/listing/filter-property-select.plugin';

export default class CustomFilterPropertySelectPlugin extends FilterPropertySelectPlugin {
    refreshDisabledState(filter) {
        // Prevent disabling if propertyName is not set correctly or if is category filter
        if (this.options.propertyName === '' || (this.options.name === 'category' && this.options.propertyName === null)) {
            return;
        }

        const activeItems = [];
        const properties = filter[this.options.name];
        const entities = properties.entities;

        if (!entities) {
            this.disableFilter();
            return;
        }


        const property = entities.find(entity => entity.translated.name === this.options.propertyName);
        if (property) {
            activeItems.push(...property.options);
        } else {
            this.disableFilter();
            return;
        }

        const actualValues = this.getValues();

        if (activeItems.length < 1 && actualValues.properties.length === 0) {
            this.disableFilter();
            return;
        } else {
            this.enableFilter();
        }

        if (actualValues.properties.length > 0) {
            return;
        }

        this._disableInactiveFilterOptions(activeItems.map(entity => entity.id));
    }
}
