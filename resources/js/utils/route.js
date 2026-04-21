import { usePage } from '@inertiajs/vue3';

export function localeRouteHelpers() {
    const page = usePage();

    const useRouteWithLocale = (name, params = {}) => {
        return route(name, {
            locale: page.props.locale,
            ...params,
        });
    };

    const switchLocale = (locale) => {
        return route(route().current(), {
            ...route().params,
            locale,
        });
    };

    return { useRouteWithLocale, switchLocale };
}
