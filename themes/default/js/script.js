'use strict';

var oelna = window.oelna || {};
oelna.gw = window.oelna.gw || {};

oelna.gw.elements = {
	'html': document.documentElement,
	'content': document.querySelector('main > .content'),
	// 'commitDiffRadioButtons': document.querySelectorAll('.select-commit'),
	// 'showDiffButton': document.querySelector('#show-diff')
}

oelna.gw.status = {
	'currentPage': '',
	'selectedDiffRadioButtons': []
}

const output = document.querySelector('#preview');

document.addEventListener('DOMContentLoaded', function (event) {
	const parser = new showdown.Converter();

	const output = document.querySelector('#preview');
	const textarea = document.querySelector('textarea[name="page_content"]');
	if (textarea !== null) {
		textarea.addEventListener('keyup', function (event) {
			// todo: add delay?
			// todo: only parse on change
			const html = parser.makeHtml(event.target.value);
			output.innerHTML = html;
		});
	}

	// diff engine
	if (oelna.gw.elements.content.classList.contains('diff')) {
		const diffInput = document.querySelector('#diff-input');
		if (diffInput !== null) {
			let diffHtml = window.Diff2Html.html(diffInput.value, {
				'drawFileList': false,
				'matching': 'lines',
				'outputFormat': 'side-by-side',
			});

			document.querySelector('#diff-output').innerHTML = diffHtml;
		}
	}

	
	const commitDiffRadioButtons = document.querySelectorAll('.select-commit');
	if (commitDiffRadioButtons !== null) {
		commitDiffRadioButtons.forEach(function (ele, i) {
			ele.addEventListener('change', function (event) {
				event.preventDefault();

				// enable the diff button
				if (oelna.gw.status.selectedDiffRadioButtons.length > 0) {
					document.querySelector('#show-diff').disabled = false;
				}

				if (oelna.gw.status.selectedDiffRadioButtons.length >= 2) {
					const removedEle = oelna.gw.status.selectedDiffRadioButtons.shift();
					removedEle.checked = false;
				}
				oelna.gw.status.selectedDiffRadioButtons.push(event.target);
			});
		});
	}

	const showDiffButton = document.querySelector('#show-diff');
	if (showDiffButton !== null) {
		showDiffButton.addEventListener('click', function (event) {
			if (oelna.gw.status.selectedDiffRadioButtons.length < 2) return false;

			const homePath = document.querySelector('head').dataset.home;
			const currentPage = (document.documentElement.dataset.page.length > 0) ? document.documentElement.dataset.page : '';
			const url = homePath+'/'+currentPage+'/diff/'+oelna.gw.status.selectedDiffRadioButtons[1].value+'/'+oelna.gw.status.selectedDiffRadioButtons[0].value+'/';

			// deselect the radio buttons first, in case the user navigates back
			if (commitDiffRadioButtons !== null) {
				commitDiffRadioButtons.forEach(function (ele, i) {
					ele.checked = false;
				});
			}

			window.location.href = url;
		});
	}

	document.querySelectorAll('.tab-container').forEach(function (e, i) {
		const tabs = e.querySelectorAll('.tabs > *');

		const tabNavItems = e.querySelectorAll('.tab-nav li').forEach(function (e, i) {
			e.addEventListener('click', function (event) {
				const tabContainer = event.target.closest('.tab-container');

				tabContainer.querySelectorAll('.tab-nav .active').forEach(function (e, i) {
					e.classList.remove('active');
				});
				tabContainer.querySelectorAll('.tabs > .active').forEach(function (e, i) {
					e.classList.remove('active');
				});

				event.target.classList.add('active');
				tabContainer.querySelector('#'+event.target.dataset.target).classList.add('active');
				
				// console.log('clicked tab', event.target.dataset.target);
			});
		});
	});
});