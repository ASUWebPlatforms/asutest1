/**
 * Drupal initializer.
 * Launch as behavior and pull variables from config.
 */

Drupal.behaviors.editoria11yAdmin = {
    attach: function (context, settings) {

        if (context === document && CSS.supports("selector(:is(body))")) {
            let dataSrc = document.querySelector('[href*="#ed11y-purge"], #ed11y-rowpurger');
            
            if (!!dataSrc) {
                const messages = new Drupal.Message();

                function handleErrors(data) {
                    console.error("Reset failed.");
                    messages.add(`${data.message}: ${data.description}`, {type: 'warning'});
                    return;
                }

                if (dataSrc.getAttribute('id') === 'ed11y-rowpurger') {
                    let rows = dataSrc.nextElementSibling?.querySelectorAll('tr');
                    let isHead = true;
                    rows?.forEach((row, i) => {
                        if (isHead) {
                            let th = document.createElement('th')
                            th.textContent = 'Reset';
                            row.appendChild(th);
                            isHead = false;
                        } else {
                            let td = document.createElement('td');
                            let describer = row.querySelector('td:first-child a');
                            describer?.setAttribute('id', 'describer' + i);
                            let button = document.createElement('button');
                            button.setAttribute('class', 'button button--extrasmall ed11y-reset-dismissal');
                            button.setAttribute('aria-describedby', 'describer' + i);
                            button.setAttribute('aria-label', 'reset');
                            button.innerHTML = '&nbsp;';
                            td.appendChild(button);
                            row.appendChild(td);
                        }
                    })
                }

                let apiUrl = drupalSettings.editoria11y.api_url;
                let sessionUrl = drupalSettings.editoria11y.session_url;
                
                let csrfToken = false;
                let getCsrfToken = async function(data, action) 
                {
                    {
                    fetch(`${sessionUrl}`, {
                                method: "GET"
                            })
                    .then(res => res.text())
                    .then(token => {
                            csrfToken = token;
                            postData(data, action).catch((error) => {
                                console.log(error);
                            })
                        })
                    .catch((error) => {
                        console.log(error);
                    })
                    }
                }

                let postData = async function (data, action) {
                    if (!csrfToken) {
                        getCsrfToken(data, action);
                    } else {
                        let apiRoot = apiUrl.replace('results/report','purge');
                        // NEED TO REPLACE APIROOT
                        let url = `${apiRoot}/${action}`;
                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-Token': csrfToken,
                            },
                            body: JSON.stringify(data),
                        })
                        .catch((error) => {
                            console.log(error);
                        })
                        .then((response) => response.json())
                        .then((data) => {
                            if (data.message === 'error') {
                                handleErrors(data);
                            };
                        })
                    }
                }
                
                let purgeThisPage = function (event) {
                    event.preventDefault;
                    let pagePath = '';
                    if (event.target.getAttribute('data-target') === 'page') {
                        pagePath = document.querySelector('#ed11y-page a').getAttribute('href');
                    }
                    let data = {
                        page_path: pagePath,
                    };
                    postData(data, 'page');
                    event.target.previousElementSibling.querySelector('tbody')?.remove();
                    event.target.previousElementSibling.querySelector('a')?.focus();
                    event.target.remove();
                }

                let purgeThisDismissal = function(event) {
                    event.preventDefault;
                    let tr = event.target.closest('tr');
                    let pagePath = tr.querySelector('td:first-child a').getAttribute('href');
                    let resultName = tr.querySelector('td:nth-child(2)').textContent;
                    let marked = tr.querySelector('td:nth-child(3)').textContent;
                    let by = tr.querySelector('td:nth-child(4)').getAttribute('data-uid');
                    by = by.replace('uid','');
                    let data = {
                        page_path: pagePath,
                        result_name: resultName,
                        marked: marked,
                        by: by
                    };
                    postData(data, 'dismissal', tr);
                    let previous = tr.previousElementSibling;
                    if (!previous) {
                        previous = document.querySelector('#editoria11y-form-dashboard, #editoria11y-form-dismissal-filters');
                    }
                    previous?.querySelector('button, summary')?.focus(); 
                    tr.remove()
                }

                let pagePurger = document.querySelector('[href*="#ed11y-purge"]');
                pagePurger?.addEventListener('click', purgeThisPage);

                let dismissalPurger = document.querySelectorAll('.ed11y-reset-dismissal');
                dismissalPurger?.forEach(el => {el.addEventListener('click', purgeThisDismissal)});
            }
        }
    }
};
