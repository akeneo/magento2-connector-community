define([
    'uiRegistry'
], function (registry) {
    'use strict';
    return function (config) {
        if (config.autoReloadEnabled) {
            const watchableStatusIds = JSON.parse(config.watchableStatusIds);
            const autoReloadInterval = setInterval(
                () => {
                    const registrySource = registry.get('index = akeneo_job_listing')
                        .source;
                    registrySource.reload({'refresh': true})
                    const rows = registrySource.data.items;
                    const watchableRows = rows.filter(
                        row => {
                            for (let id of watchableStatusIds) {
                                if (parseInt(row.raw_status) === id) return true;
                            }
                            return false;
                        }
                    );
                    if (!watchableRows.length) {
                        clearInterval(autoReloadInterval);
                    }
                }, 5000
            )
        }
    }
});
