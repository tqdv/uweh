// Public namespace
var Uweh = Uweh || {};
Uweh.php = Uweh.php || {};

(function () {

// === Utilities ===

// Unhide an element using the CSS hidden class
function unhide (elt) {
	elt.classList.remove("hidden")
}

// Check if the file is a directory by trying to read it
async function file_is_directory (file) {
	return await new Promise((resolve) => {
		let fr = new FileReader()
		let aborted = false

		fr.addEventListener('progress', () => {
			// Order matters
			aborted = true
			fr.abort()
		})
		fr.addEventListener('loadend', () => {
			let is_dir = fr.error !== null && !aborted
			resolve(is_dir)
		})
		
		fr.readAsArrayBuffer(file)
	})
}

// Disable drag and drop on the page
Uweh.disable_drag_drop = function () {
	document.body.addEventListener('dragover', e => e.preventDefault())
	document.body.addEventListener('drop', e => e.preventDefault())
};

// === PHP <-> JS ===
// PHP's data is available through the `Uweh.php` object.

// Returns if File has an allowed extension, based on Uweh.php.extlist and Uweh.php.filteringMode
function is_extension_allowed(file) {
	let i = file.name.lastIndexOf('.')
	let ext = file.name.substring(i + 1) // If i == -1, then it still works
	let res = Uweh.php.extlist.split(',').includes(ext)
	if (Uweh.php.filteringMode === 'GRANTLIST') {
		return res
	} else if (Uweh.php.filteringMode === 'NONE') {
		return true
	} else { // 'BLOCKLIST'
		return !res
	}
}

// Returns if the File has an acceptable size based on Uweh.php.maxFilesize
function valid_file_size (file) {
	return 0 < file.size && file.size <= Uweh.php.maxFilesize
}

// Returns the download url which is Uweh.php.downloadUrl
function get_download_url () {
	return Uweh.php.downloadUrl;
}

// === Form ===

/** File input controller to synchronize state between the file_input and the upload_btn
 * 
 * It checks that the file_input is valid, otherwise it disables the upload_btn
 * and set the `invalid-file` class on file_input (which adds a red border)
 * 
 * The file's validity is based on its extension, its size and if it's a file or not.
 * This depends on Uweh.{extlist, filteringMode, maxFilesize}
 */
Uweh.FileInput = class {
	/**
	 * @param {Element} file_input The file input element to check
	 * @param {Element} upload_btn The upload button to disable
	 */
	constructor (file_input, upload_btn) {
		this.file_input = file_input;
		this.upload_btn = upload_btn;
	}

	addListeners() {
		this.file_input.addEventListener('change', async () => await this.checkInput());
	}

	disableInput() {
		this.file_input.classList.add('invalid-file');
		this.upload_btn.setAttribute('disabled', '');
	}

	enableInput() {
		this.file_input.classList.remove('invalid-file');
		this.upload_btn.removeAttribute('disabled');
	}

	async checkInput() {
		let files = this.file_input.files;

		let invalid_file = Array.from(files).some((v) => !(is_extension_allowed(v) && valid_file_size(v)));
		let too_many_files = files.length > 1;
		let is_single_dir = files.length == 1 && await file_is_directory(files[0]);

		if (invalid_file || too_many_files || is_single_dir) {
			this.disableInput();
		} else {
			this.enableInput();
		}
	}
};

/** Drag and drop a file on dropzone to set uweh_file_input
 * 
 * This class defines the event handlers needed for the drag and drop to work.
 * When something is being dragged over the dropzone, the CSS class `file-dragover` is set.
 */
Uweh.Drop = class {
	/**
	 * @param {Element} dropzone The file drop zone
	 * @param {Element} uweh_file_input The file input to update
	 */
	constructor (dropzone, uweh_file_input) {
		this.dropzone = dropzone;
		this.uweh_file_input = uweh_file_input;
	}

	addListeners() {
		this.dropzone.addEventListener('drop', e => this.handleDrop(e));
		this.dropzone.addEventListener('dragenter', () => this.addDragClass());
		this.dropzone.addEventListener('dragleave', () => this.removeDragClass());
		this.dropzone.addEventListener('dragover', e => { e.preventDefault(); this.addDragClass() });
	}

	handleDrop (e) {
		this.uweh_file_input.file_input.files = e.dataTransfer.files;
		this.uweh_file_input.checkInput();

		this.removeDragClass();
		e.preventDefault();
	}

	removeDragClass() {
		this.dropzone.classList.remove('file-dragover');
	}

	addDragClass() {
		this.dropzone.classList.add('file-dragover');
	}
};

/** Enable checking the file input file, and disabling the button if it's not valid
 * 
 * This function returns the Uweh.FileInput object on success, and null on failure.
 * Failure occurs when the required properties in `Uweh.php` aren't present.
 * 
 * @param {Element} file_input The file input to check
 * @param {Element} upload_btn The button to disable
 * @returns {?Uweh.FileInput} The Uweh.FileInput on success, null on failure
 */
Uweh.enableFileInputCheck = function (file_input, upload_btn) {
	let props = ["extlist", "filteringMode", "maxFilesize"]
	for (prop of props) {
		if (!(prop in Uweh.php)) {
			console.log(`[Uweh] enableFileInputCheck requires Uweh.php.${prop}`)
			return null
		}
	}

	let uweh_file_input = new Uweh.FileInput(file_input, upload_btn);
	uweh_file_input.addListeners();

	return uweh_file_input
};

/** Enable drag and drop on dropzone which sets uweh_file_input and display feature infotext
 * 
 * The function fails if the DataTransferItem API is not implemented.
 *
 * @param {Element} dropzone The file dropzone
 * @param {Uweh.FileInput} uweh_file_input The file input abstraction
 * @param {Element} infotext The text to unhide
 * @returns {boolean} if drag and drop was successfully enabled
 */

Uweh.enableDragDrop = function (dropzone, uweh_file_input, infotext) {
	if (!('DataTransferItem' in window)) return false

	let uweh_drop = new Uweh.Drop(dropzone, uweh_file_input)
	uweh_drop.addListeners()

	unhide(infotext)
	return true
};

// === Upload success ===

/**
 * Adds a click handler on copy_link that sets the clipboard to the download url (based on Uweh.php.downloadUrl)
 * and displays an animated success message on success_span using the CSS `animation-copied` class.
 * 
 * This function fails if the clipboard is not accessible (eg. insecure context)
 * or if Uweh.php.downloadUrl is missing
 *  
 * @param {Element} copy_link The element to click on
 * @param {Element} success_span The span to animate on success
 * @returns {boolean} if it has successfully assigned the handler
 */
Uweh.enableCopyLinkButton = function (copy_link, success_span) {
	// NB The timeout is hardcoded to 1500ms, check with animation-copied's duration

	// Copy to clipboard only works in secure contexts aka https
	if (!navigator.clipboard) return false
	if (!('downloadUrl' in Uweh.php)) {
		console.log("[Uweh] enableCopyLinkButton requires Uweh.php.downloadUrl")
		return false
	}

	// Grab elements
	let success_parent = success_span.parentNode

	// Set behaviour
	let timer_id = null
	copy_link.addEventListener("click", () => {
		clearTimeout(timer_id)
		navigator.clipboard.writeText(get_download_url())
		.then(() => {
			// Replace child to interrupt animation
			let replacement = success_span.cloneNode(true)
			success_parent.replaceChild(replacement, success_span)
			success_span = replacement

			// Set content (for the first time)
			success_span.textContent = "Copied !"
			success_span.classList.add("animation-copied")

			// Delete after timeout
			timer_id = setTimeout(() => {
				success_span.textContent = ""
				success_span.classList.remove("animation-copied")
			}, 1500);
		})
	})
	
	// Display button
	unhide(copy_link)
	return true
};

})();