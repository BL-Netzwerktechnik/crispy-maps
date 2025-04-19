const uploadTargetHash = 'l1_Lw';

// elFinder connector URL
const connectorUrl = '/admin/files/connector';
import {
	ClassicEditor,
	Alignment,
	Autoformat,
	AutoImage,
	AutoLink,
	Autosave,
	BalloonToolbar,
	BlockQuote,
	BlockToolbar,
	Bold,
	Bookmark,
	CloudServices,
	Code,
	CodeBlock,
	Essentials,
	FindAndReplace,
	FontBackgroundColor,
	FontColor,
	FontFamily,
	FontSize,
	FullPage,
	GeneralHtmlSupport,
	Heading,
	Highlight,
	HorizontalLine,
	HtmlComment,
	HtmlEmbed,
	ImageBlock,
	ImageCaption,
	ImageInline,
	ImageInsertViaUrl,
	ImageResize,
	ImageStyle,
	ImageTextAlternative,
	ImageToolbar,
	ImageUpload,
	Indent,
	IndentBlock,
	Italic,
	Link,
	LinkImage,
	List,
	ListProperties,
	MediaEmbed,
	PageBreak,
	Paragraph,
	PasteFromOffice,
	RemoveFormat,
	ShowBlocks,
	SourceEditing,
	SpecialCharacters,
	SpecialCharactersArrows,
	SpecialCharactersCurrency,
	SpecialCharactersEssentials,
	SpecialCharactersLatin,
	SpecialCharactersMathematical,
	SpecialCharactersText,
	Strikethrough,
	Style,
	Subscript,
	Superscript,
	Table,
	TableCaption,
	TableCellProperties,
	TableColumnResize,
	TableProperties,
	TableToolbar,
	TextTransformation,
	TodoList,
	Underline,
	WordCount,
	CKFinder,
	CKFinderUploadAdapter
} from 'ckeditor5';

const LICENSE_KEY = 'GPL'; // or <YOUR_LICENSE_KEY>.

const editorConfig = {
	toolbar: {
		items: [
			'sourceEditing',
			'showBlocks',
			'findAndReplace',
			'|',
			'heading',
			'style',
			'|',
			'fontSize',
			'fontFamily',
			'fontColor',
			'fontBackgroundColor',
			'|',
			'bold',
			'italic',
			'underline',
			'strikethrough',
			'subscript',
			'superscript',
			'code',
			'removeFormat',
			'|',
			'specialCharacters',
			'horizontalLine',
			'pageBreak',
			'link',
			'bookmark',
			'mediaEmbed',
			'insertTable',
			'highlight',
			'blockQuote',
			'codeBlock',
			'htmlEmbed',
			'|',
			'alignment',
			'|',
			'bulletedList',
			'numberedList',
			'todoList',
			'outdent',
			'indent',
			'ckfinder'
		],
		shouldNotGroupWhenFull: true
	},
	plugins: [
		Alignment,
		Autoformat,
		CKFinderUploadAdapter,
		AutoImage,
		AutoLink,
		Autosave,
		BalloonToolbar,
		CKFinder,
		BlockQuote,
		BlockToolbar,
		Bold,
		Bookmark,
		CloudServices,
		Code,
		CodeBlock,
		Essentials,
		FindAndReplace,
		FontBackgroundColor,
		FontColor,
		FontFamily,
		FontSize,
		FullPage,
		GeneralHtmlSupport,
		Heading,
		Highlight,
		HorizontalLine,
		HtmlComment,
		HtmlEmbed,
		ImageBlock,
		ImageCaption,
		ImageInline,
		ImageInsertViaUrl,
		ImageResize,
		ImageStyle,
		ImageTextAlternative,
		ImageToolbar,
		ImageUpload,
		Indent,
		IndentBlock,
		Italic,
		Link,
		LinkImage,
		List,
		ListProperties,
		MediaEmbed,
		PageBreak,
		Paragraph,
		PasteFromOffice,
		RemoveFormat,
		ShowBlocks,
		SourceEditing,
		SpecialCharacters,
		SpecialCharactersArrows,
		SpecialCharactersCurrency,
		SpecialCharactersEssentials,
		SpecialCharactersLatin,
		SpecialCharactersMathematical,
		SpecialCharactersText,
		Strikethrough,
		Style,
		Subscript,
		Superscript,
		Table,
		TableCaption,
		TableCellProperties,
		TableColumnResize,
		TableProperties,
		TableToolbar,
		TextTransformation,
		TodoList,
		Underline,
		WordCount
	],
	balloonToolbar: ['bold', 'italic', '|', 'link', '|', 'bulletedList', 'numberedList'],
	blockToolbar: [
		'fontSize',
		'fontColor',
		'fontBackgroundColor',
		'|',
		'bold',
		'italic',
		'|',
		'link',
		'insertTable',
		'|',
		'bulletedList',
		'numberedList',
		'outdent',
		'indent'
	],
	fontFamily: {
		supportAllValues: true
	},
	fontSize: {
		options: [10, 12, 14, 'default', 18, 20, 22],
		supportAllValues: true
	},
	heading: {
		options: [
			{
				model: 'paragraph',
				title: 'Paragraph',
				class: 'ck-heading_paragraph'
			},
			{
				model: 'heading1',
				view: 'h1',
				title: 'Heading 1',
				class: 'ck-heading_heading1'
			},
			{
				model: 'heading2',
				view: 'h2',
				title: 'Heading 2',
				class: 'ck-heading_heading2'
			},
			{
				model: 'heading3',
				view: 'h3',
				title: 'Heading 3',
				class: 'ck-heading_heading3'
			},
			{
				model: 'heading4',
				view: 'h4',
				title: 'Heading 4',
				class: 'ck-heading_heading4'
			},
			{
				model: 'heading5',
				view: 'h5',
				title: 'Heading 5',
				class: 'ck-heading_heading5'
			},
			{
				model: 'heading6',
				view: 'h6',
				title: 'Heading 6',
				class: 'ck-heading_heading6'
			}
		]
	},
	htmlSupport: {
		allow: [
			{
				name: /^.*$/,
				styles: true,
				attributes: true,
				classes: true
			}
		]
	},
	image: {
		resizeUnit: 'px',
		resizeOptions: [
			{
				name: 'resizeImage:original',
				label: 'Original',
				value: null
			},
			{
				name: 'resizeImage:custom',
				label: 'Custom',
				value: 'custom'
			},
			{
				name: 'resizeImage:100',
				label: '100px',
				value: '100'
			},
			{
				name: 'resizeImage:200',
				label: '200px',
				value: '200'
			}
		],
		toolbar: [
			'toggleImageCaption',
			'imageTextAlternative',
			'|',
			'imageStyle:inline',
			'imageStyle:wrapText',
			'imageStyle:breakText',
			'|',
			'resizeImage'
		]
	},
	licenseKey: LICENSE_KEY,
	link: {
		addTargetToExternalLinks: true,
		defaultProtocol: 'https://',
		decorators: {
			toggleDownloadable: {
				mode: 'manual',
				label: 'Downloadable',
				attributes: {
					download: 'file'
				}
			}
		}
	},
	list: {
		properties: {
			styles: true,
			startIndex: true,
			reversed: true
		}
	},
	placeholder: 'Type or paste your content here!',
	style: {
		definitions: [
			{
				name: 'Article category',
				element: 'h3',
				classes: ['category']
			},
			{
				name: 'Title',
				element: 'h2',
				classes: ['document-title']
			},
			{
				name: 'Subtitle',
				element: 'h3',
				classes: ['document-subtitle']
			},
			{
				name: 'Info box',
				element: 'p',
				classes: ['info-box']
			},
			{
				name: 'Side quote',
				element: 'blockquote',
				classes: ['side-quote']
			},
			{
				name: 'Marker',
				element: 'span',
				classes: ['marker']
			},
			{
				name: 'Spoiler',
				element: 'span',
				classes: ['spoiler']
			},
			{
				name: 'Code (dark)',
				element: 'pre',
				classes: ['fancy-code', 'fancy-code-dark']
			},
			{
				name: 'Code (bright)',
				element: 'pre',
				classes: ['fancy-code', 'fancy-code-bright']
			}
		]
	},
	table: {
		contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties']
	}
};


// Initialize CKEditor 5 with all tools
ClassicEditor.create(document.querySelector("#wysiwyg_editor"), editorConfig).then(editor => {

	const ckf = editor.commands.get('ckfinder'),
		fileRepo = editor.plugins.get('FileRepository'),
		ntf = editor.plugins.get('Notification'),
		i18 = editor.locale.t,
		// Insert images to editor window
		insertImages = urls => {
			const imgCmd = editor.commands.get('imageUpload');
			if (!imgCmd.isEnabled) {
				ntf.showWarning(i18('Could not insert image at the current position.'), {
					title: i18('Inserting image failed'),
					namespace: 'ckfinder'
				});
				return;
			}
			editor.execute('imageInsert', { source: urls });
		},
		// To get elFinder instance
		getfm = open => {
			return new Promise((resolve, reject) => {
				// Execute when the elFinder instance is created
				const done = () => {
					if (open) {
						// request to open folder specify
						if (!Object.keys(_fm.files()).length) {
							// when initial request
							_fm.one('open', () => {
								_fm.file(open) ? resolve(_fm) : reject(_fm, 'errFolderNotFound');
							});
						} else {
							// elFinder has already been initialized
							new Promise((res, rej) => {
								if (_fm.file(open)) {
									res();
								} else {
									// To acquire target folder information
									_fm.request({ cmd: 'parents', target: open }).done(e => {
										_fm.file(open) ? res() : rej();
									}).fail(() => {
										rej();
									});
								}
							}).then(() => {
								// Open folder after folder information is acquired
								_fm.exec('open', open).done(() => {
									resolve(_fm);
								}).fail(err => {
									reject(_fm, err ? err : 'errFolderNotFound');
								});
							}).catch((err) => {
								reject(_fm, err ? err : 'errFolderNotFound');
							});
						}
					} else {
						// show elFinder manager only
						resolve(_fm);
					}
				};

				// Check elFinder instance
				if (_fm) {
					// elFinder instance has already been created
					done();
				} else {
					// To create elFinder instance
					_fm = $('<div/>').dialogelfinder({
						// dialog title
						title: translation['CMSControl.Views.Pages.ElFinder.Title'],
						// connector URL
						url: connectorUrl,
						// start folder setting
						startPathHash: open ? open : void (0),
						// Set to do not use browser history to un-use location.hash
						useBrowserHistory: false,
						// Disable auto open
						autoOpen: false,
						openMaximized: true,
						allowMinimize: true,
						// elFinder dialog width
						width: '40%',
						// set getfile command options
						commandsOptions: {
							getfile: {
								oncomplete: 'close',
								multiple: true
							}
						},
						// Insert in CKEditor when choosing files
						getFileCallback: (files, fm) => {
							let imgs = [];
							fm.getUI('cwd').trigger('unselectall');
							$.each(files, function (i, f) {
								if (f && f.mime.match(/^image\//i)) {
									imgs.push(fm.convAbsUrl(f.url));
								} else {
									editor.execute('link', fm.convAbsUrl(f.url));
								}
							});
							if (imgs.length) {
								insertImages(imgs);
							}
						}
					}).elfinder('instance');
					done();
				}
			});
		};

	// elFinder instance
	let _fm;

	if (ckf) {
		// Take over ckfinder execute()
		ckf.execute = () => {
			getfm().then(fm => {
				fm.getUI().dialogelfinder('open');
			});
		};
	}

	// Make uploader
	const uploder = function (loader) {
		let upload = function (file, resolve, reject) {
			getfm(uploadTargetHash).then(fm => {
				let fmNode = fm.getUI();
				fmNode.dialogelfinder('open');
				fm.exec('upload', { files: [file], target: uploadTargetHash }, void (0), uploadTargetHash)
					.done(data => {
						if (data.added && data.added.length) {
							fm.url(data.added[0].hash, { async: true }).done(function (url) {
								resolve({
									'default': fm.convAbsUrl(url)
								});
								fmNode.dialogelfinder('close');
							}).fail(function () {
								reject('errFileNotFound');
							});
						} else {
							reject(fm.i18n(data.error ? data.error : 'errUpload'));
							fmNode.dialogelfinder('close');
						}
					})
					.fail(err => {
						const error = fm.parseError(err);
						reject(fm.i18n(error ? (error === 'userabort' ? 'errAbort' : error) : 'errUploadNoFiles'));
					});
			}).catch((fm, err) => {
				const error = fm.parseError(err);
				reject(fm.i18n(error ? (error === 'userabort' ? 'errAbort' : error) : 'errUploadNoFiles'));
			});
		};

		this.upload = function () {
			return new Promise(function (resolve, reject) {
				if (loader.file instanceof Promise || (loader.file && typeof loader.file.then === 'function')) {
					loader.file.then(function (file) {
						upload(file, resolve, reject);
					});
				} else {
					upload(loader.file, resolve, reject);
				}
			});
		};
		this.abort = function () {
			_fm && _fm.getUI().trigger('uploadabort');
		};
	};

	// Set up image uploader
	fileRepo.createUploadAdapter = loader => {
		return new uploder(loader);
	};
	editorInstance = editor;

}).catch(error => console.error(error));