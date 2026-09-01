const PluginManager = window.PluginManager;
PluginManager.override('FilterPropertySelect', () => import('./custom-filter-property-select/custom-filter-property-select.plugin'), '[data-filter-property-select]');

