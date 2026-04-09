import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \Flatpack\Http\Controllers\FlatpackSessionController::store
* @route '/flatpack/login'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/flatpack/login',
} satisfies RouteDefinition<["post"]>

/**
* @see \Flatpack\Http\Controllers\FlatpackSessionController::store
* @route '/flatpack/login'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \Flatpack\Http\Controllers\FlatpackSessionController::store
* @route '/flatpack/login'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \Flatpack\Http\Controllers\FlatpackSessionController::store
* @route '/flatpack/login'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \Flatpack\Http\Controllers\FlatpackSessionController::store
* @route '/flatpack/login'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const login = {
    store: Object.assign(store, store),
}

export default login