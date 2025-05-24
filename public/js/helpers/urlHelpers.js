require('url-polyfill'); // Need url-polyfill to be installed on project

export const addUrlQueryParams = (url, params) => {

    let parsedUrl = new URL(url);

    if(params) {
        Object.keys(params).map((key) => {
            parsedUrl.searchParams.set(key, params[key]);
        });
    }

    return parsedUrl.href;
};
