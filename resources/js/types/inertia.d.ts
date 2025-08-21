// resources/js/types/inertia.d.ts
// No import needed, just declare the module

declare module '@inertiajs/core' {
    // We are *merging* with the existing interface
    interface FormOptions {
        /**
         * Optional function to transform the form into FormData
         */
        transform?: () => FormData;
    }
}
