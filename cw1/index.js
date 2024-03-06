window.addEventListener('load', function (evt) {

    const my_form = this.document.querySelector('#my_form form'),
        my_search = this.document.querySelector('#my_search'),
        my_records = this.document.querySelector('#my_records'),
        my_error = this.document.querySelector('#my_error'),
        my_loading = this.document.querySelector('#my_loading');

    my_form.addEventListener('submit', function (evt) {
        // prevent submit default behaviour
        evt.preventDefault();

        // clear previous search results
        my_error.style.display = 'none';

        while (my_records.lastChild) {
            my_records.removeChild(my_records.lastChild);
        }

        // client side form validation
        let val_query = my_search.value.trim();

        if (val_query.length === 0) {
            document.querySelector('#hint_search').style.display = 'inline';
        }
        else {
            document.querySelector('#hint_search').style.display = 'none';
            sendRequest(val_query);
        }
    })

    async function sendRequest(query) {
        my_loading.style.display = 'block';

        // assemble the full url
        let url = `https://api.vam.ac.uk/v2/objects/search?q=${encodeURIComponent(query)}&images=true&page_size=60`;

        try {
            const response = await fetch(url);
            const json = await response.json();

            my_loading.style.display = 'none';

            // check if there were no records present
            if (json.records.length === 0) {
                my_error.textContent = `Sorry, no results found for ${query}. Please try a different search term or refine your query.`;
                my_error.style.display = 'block';
            }
            else {
                populateRecords(json);
            }
        }
        catch (error) {
            console.log(error);

            my_loading.style.display = 'none';
            my_error.textContent = `Oops - something went wrong`;
            my_error.style.display = 'block';
        }
    }

    function populateRecords(json) {

        for (record of json.records) {
            const card = document.createElement('div');
            card.setAttribute('class', 'card');

            const card_content = document.createElement('div');
            card_content.setAttribute('class', 'card_content');

            const title = document.createElement('a');
            title.setAttribute('class', 'card_content_title');
            title.href = `https://collections.vam.ac.uk/item/${record.systemNumber}`;
            title.target = '_blank';
            const val_title = record._primaryTitle ? record._primaryTitle : record.objectType;
            title.textContent = val_title;

            const maker = document.createElement('p');
            const val_maker = record._primaryMaker.name ? record._primaryMaker.name : 'Unknown';
            maker.textContent = `Artist/Maker: ${val_maker}`;

            const date = document.createElement('p');
            const val_date = record._primaryDate ? record._primaryDate : 'Unknown';
            date.textContent = `Date: ${val_date}`;

            const loc = document.createElement('p');
            const val_loc = record._currentLocation.displayName.toLowerCase() === 'in store' ? 'Not currently on display' : record._currentLocation.displayName;
            loc.textContent = `Object location: ${val_loc}`;

            const clickable_img = document.createElement('a');
            clickable_img.href = `https://collections.vam.ac.uk/item/${record.systemNumber}`;
            clickable_img.target = '_blank';

            const img = document.createElement('img');
            img.setAttribute('alt', record._primaryTitle === '' ? record.objectType : record._primaryTitle);
            if (record._primaryImageId) {
                img.setAttribute('src', `https://framemark.vam.ac.uk/collections/${record._primaryImageId}/full/735,/0/default.jpg`);
            }
            else {
                // https://iconduck.com/
                img.setAttribute('src', 'no-image.1024x1024.png');
            }

            img.addEventListener('error', function (evt) {
                img.setAttribute('src', 'no-image.1024x1024.png');
            })

            clickable_img.appendChild(img);
            card.appendChild(clickable_img);
            card_content.appendChild(title);
            card_content.appendChild(maker);
            card_content.appendChild(date);
            card_content.appendChild(loc);
            card.appendChild(card_content);

            my_records.appendChild(card);

        }

    }



})