import { usePage } from '@inertiajs/vue3';

export function useRouteWithLocale() {
    const page = usePage();

    return (name, params = {}) => {
        return route(name, {
            locale: page.props.locale,
            ...params,
        });
    };
}
