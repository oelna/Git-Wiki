'use strict';

var oelna = window.oelna || {};
oelna.gw = window.oelna.gw || {};

oelna.gw.elements = {
	'content': document.querySelector('main > .content')
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