define([
    'uiRegistry'
], function (registry) {
    'use strict';
    return function (config) {
        if (config.autoReloadEnabled) {
            setInterval(
                () => {
                    registry.get('index = akeneo_job_listing')
                    .source
                    .reload({'refresh': true})
                }, 5000
            )
        }
    }
});
