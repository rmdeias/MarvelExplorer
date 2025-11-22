/**
 * Generic module for AJAX search and pagination.
 *
 * Usage:
 *   import { createSearchPagination } from '/assets/js/searchByTitleOrName.js';
 *   createSearch({
 *       searchInputSelector: '#search-bar',
 *       listContainerSelector: '#comic-list',
 *       fetchUrl: '/comics/search',
 *       queryParam: 'title',  // or 'name' for characters
 *       minChars: 3,
 *       defaultHtml: `{% include 'comics/_list.html.twig' ... %}`
 *   });
 *
 * Options:
 *   searchInputSelector   : CSS selector for the search input field
 *   listContainerSelector : CSS selector for the container where results will be injected
 *   fetchUrl              : API URL to fetch search results
 *   queryParam            : Name of the search query parameter (title, name)
 *   minChars              : Minimum number of characters to trigger the AJAX search (default: 3)
 *   defaultHtml           : Default HTML to display if search is not triggered
 */
export function createSearch({
                                 searchInputSelector,
                                 listContainerSelector,
                                 fetchUrl,
                                 queryParam,
                                 minChars = 3,
                                 defaultHtml = '',
                             }) {
    const searchInput = document.querySelector(searchInputSelector);
    const listContainer = document.querySelector(listContainerSelector);

    let currentQuery = '';
    let currentPage = 1;

    function loadItems(query, page = 1) {
        currentQuery = query;
        currentPage = page;

        const url = `${fetchUrl}?${queryParam}=${encodeURIComponent(query)}&page=${page}`;

        fetch(url)
            .then(res => res.text())
            .then(html => {
                listContainer.innerHTML = html;

                // Pagination AJAX
                listContainer.querySelectorAll('.pagination a').forEach(link => {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        const newPage = parseInt(this.dataset.page);
                        loadItems(currentQuery, newPage);
                    });
                });
            })
            .catch(err => {
                console.error(err);
                listContainer.innerHTML = '<p>Erreur lors de la récupération des données.</p>';
            });
    }

    searchInput.addEventListener('input', function () {
        const query = searchInput.value.trim();
        if (query.length < minChars) {
            listContainer.innerHTML = defaultHtml;
            return;
        }
        loadItems(query, 1);
    });
}
