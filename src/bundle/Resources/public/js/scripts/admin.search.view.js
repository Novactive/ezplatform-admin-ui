(function(global, doc, $, React, ReactDOM, eZ, Routing) {
    const listContainers = doc.querySelectorAll('.ez-sil');
    const token = doc.querySelector('meta[name="CSRF-Token"]').content;
    const siteaccess = doc.querySelector('meta[name="SiteAccess"]').content;

    const generateLink = (locationId) => Routing.generate('_ezpublishLocation', { locationId });

    const handleEditItem = (content) => {
        const contentId = content._id;
        const checkVersionDraftLink = Routing.generate('ezplatform.version_draft.has_no_conflict', { contentId });
        const submitVersionEditForm = () => {
            doc.querySelector('#content_edit_content_info').value = contentId;
            doc.querySelector('#content_edit_version_info_content_info').value = contentId;
            doc.querySelector('#content_edit_version_info_version_no').value =
                content.CurrentVersion.Version.VersionInfo.versionNo;
            doc.querySelector(`#content_edit_language_${content.mainLanguageCode}`).checked = true;
            doc.querySelector('#content_edit_create').click();
        };
        const addDraft = () => {
            submitVersionEditForm();
            $('#version-draft-conflict-modal').modal('hide');
        };
        const showModal = (modalHtml) => {
            const wrapper = doc.querySelector('.ez-modal-wrapper');

            wrapper.innerHTML = modalHtml;
            const addDraftButton = wrapper.querySelector('.ez-btn--add-draft');
            if (addDraftButton) {
                addDraftButton.addEventListener('click', addDraft, false);
            }
            [...wrapper.querySelectorAll('.ez-btn--prevented')].forEach((btn) =>
                btn.addEventListener('click', (event) => event.preventDefault(), false)
            );
            $('#version-draft-conflict-modal').modal('show');
        };
        fetch(checkVersionDraftLink, {
            credentials: 'same-origin',
        }).then((response) => {
            // Status 409 means that a draft conflict has occurred and the modal must be displayed.
            // Otherwise we can go to Content Item edit page.
            if (response.status === 409) {
                response.text().then(showModal);
            } else if (response.status === 200) {
                submitVersionEditForm();
            }
        });
    };

    listContainers.forEach((container) => {
        const itemsList = JSON.parse(container.dataset.items).ItemsList;
        const items = itemsList.ItemsRow.map((item) => ({
            content: item.Content,
            location: item.Location,
        }));

        const contentTypes = JSON.parse(container.dataset.contentTypes).ContentTypeInfoList.ContentType;
        const contentTypesMap = contentTypes.reduce((total, item) => {
            total[item._href] = item;

            return total;
        }, {});

        ReactDOM.render(
            React.createElement(eZ.modules.CommonItems, {
                handleEditItem,
                generateLink,
                restInfo: { token, siteaccess },
                items,
                contentTypesMap,
                totalCount: container.dataset.totalCount
            }),
            container
        );
    });
})(window, window.document, window.jQuery, window.React, window.ReactDOM, window.eZ, window.Routing);
