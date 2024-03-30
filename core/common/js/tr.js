// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-3.0
/*global get_label, page_globals, Promise */
/*eslint no-undef: "error"*/



/**
* TR
* Text transcription functions
* Based on server side php class TR
*/
export const tr = {



	/**
	* GET_MARK_PATTERN
	* Get unified patterns for marks
	*/
	get_mark_pattern : (mark) => {

		let reg_ex = ''

		switch(mark) {

			// TC . Select timecode from tag like '00:01:25.627'
			case 'tc' :
				reg_ex = /(\[TC_([0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2}(\.[0-9]{1,3})?)_TC\])/g;
				break;

			// TC_FULL . Select complete tag like '[TC_00:01:25.627_TC]'
			case 'tc_full' :
				reg_ex = /(\[TC_[0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2}\.[0-9]{1,3}_TC\])/g;
				break;

			// TC_VALUE . Select elements from value tc like '00:01:25.627'. Used by OptimizeTC
			case 'tc_value' :
				reg_ex = /([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})(\.([0-9]{1,3}))?/g;
				break;

			// INDEX
			case 'index' :
				reg_ex = /\[\/{0,1}(index)-([a-z])-([0-9]{1,6})(-([^-]{0,22})-data:(.*?):data)?\]/g;
				break;

			case 'indexIn' :
				reg_ex = /(\[(index)-([a-z])-([0-9]{1,6})(-([^-]{0,22})-data:(.*?):data)?\])/g;
				break;

			case 'indexOut':
				reg_ex = /(\[\/(index)-([a-z])-([0-9]{1,6})(-([^-]{0,22})-data:(.*?):data)?\])/g;
				break;

			// REFERENCE
			case 'reference' :
				reg_ex = /\[\/{0,1}(reference)-([a-z])-([0-9]{1,6})(-([^-]{0,22})-data:(.*?):data)?\]/g;
				break;

			case 'referenceIn' :
				reg_ex = /(\[(reference)-([a-z])-([0-9]{1,6})(-([^-]{0,22})-data:(.*?):data)?\])/g;
				break;

			case 'referenceOut' :
				reg_ex = /(\[\/(reference)-([a-z])-([0-9]{1,6})(-([^-]{0,22})-data:(.*?):data)?\])/g;
				break;

			// SVG (From now 18-05-2018 v4.9.0, will be used to manage tags from the component component_svg)
			case 'svg' :
				reg_ex = /(\[(svg)-([a-z])-([0-9]{1,6})(-([^-]{0,22}))?-data:(.*?):data\])/g;
				break;

			// DRAW (Old svg renamed 18-05-2018. Pre 4.9.0 . Until 01-02-2024 manage images over draws js paper data. now only layer id is referred)
			case 'draw' :
				reg_ex = /(\[(draw)-([a-z])-([0-9]{1,6})(-([^-]{0,22}))?-data:(.*?):data\])/g;
				break;

			// GEO
			case 'geo' :
				reg_ex = /(\[(geo)-([a-z])-([0-9]{1,6})(-([^-]{0,22}))?-data:(.*?):data\])/g;
				break;

			// GEO_FULL . Select complete tag
			case 'geo_full' :
				reg_ex = /(\[geo-[a-z]-[0-9]{1,6}(-[^-]{0,22})?-data:(.*?):data\])/g;
				break;

			// PAGE (pdf) [page-n-1--1-data:[1]:data]
			case 'page' :
				reg_ex = /(\[(page)-([a-z])-([0-9]{1,6})(-([^-]{0,22})-data:(.*?):data)?\])/g;
				break;

			// PERSON (transcription spoken person) like [person-a-number-data:{"section_tipo":"dd15","section_id":"5"}:data]
			case 'person' :
				reg_ex = /(\[(person)-([a-z])-([0-9]{0,6})-([^-]{0,22})-data:(.*?):data\])/g;
				break;

			// NOTE (transcription annotations) like [note-n-number-data:{"section_tipo":"dd15","section_id":"5"}:data]
			case 'note' :
				reg_ex = /(\[(note)-([a-z])-([0-9]{1,6})(-([^-]{0,22}))?-data:(.*?):data\])/g;
				break;

			// LANG (transcription languages) like [lang-n-number-data:"lg-spa":data]
			case 'lang' :
				reg_ex = /(\[(lang)-([a-z])-([0-9]{1,6})(-([^-]{0,22}))?-data:(.*?):data\])/g;
				break;

			// OTHERS
			case 'p' :
				reg_ex = /(\<\/?p\>)/g;
				break;

			case 'strong' :
				reg_ex = /(\<\/?strong\>)/g;
				break;

			case 'em' :
				reg_ex = /(\<\/?em\>)/g;
				break;

			case 'apertium-notrans' :
				reg_ex = /(\<apertium-notrans\>|\<\/apertium-notrans\>)/g;
				break;

			default :
				console.error(" Exception; Error Processing Request. Error: mark: 'mark' is not valid !");
		}


		return reg_ex
	},//end get_mark_pattern



	/**
	* ADD_TAG_IMG_ON_THE_FLY
	* Convert Dédalo tags like index, tc, etc. to images
	* i.e. '[TC_00:15:12:01.000]' => '<img id="[TC_00:00:25.684_TC]" class="tc" src="" ... />'
	* This function equivalent exists in server side in class TR
	* @param string text
	* @return string text
	*/
	add_tag_img_on_the_fly : (text) => {

		if (!text || text.lenght<1) {
			return text
		}

		const tag_url = '../component_text_area/tag/?id=';

		// INDEX IN
			const pattern_indexIn = tr.get_mark_pattern('indexIn'); // id,state,label,data
			text = text.replace(pattern_indexIn, `<img id="[$2-$3-$4-$6]" src="${tag_url}[$2-$3-$4-$6]" width="34" height="15" class="index" data-type="indexIn" data-tag_id="$4" data-state="$3" data-label="$6" data-data="$7">`);

		// INDEX OUT
			const pattern_indexOut = tr.get_mark_pattern('indexOut');
			text = text.replace(pattern_indexOut, `<img id="[/\$2-$3-$4-$6]" src="${tag_url}[/\$2-$3-$4-$6]" width="34" height="15" class="index" data-type="indexOut" data-tag_id="$4" data-state="$3" data-label="$6" data-data="$7">`);

		// REFERENCE IN
			const pattern_referenceIn = tr.get_mark_pattern('referenceIn');
			text = text.replace(pattern_referenceIn, `<reference id="reference_$4" class="reference" data-type="reference" data-tag_id="$4" data-state="$3" data-label="$6" data-data="$7">`);

		// REFERENCE OUT
			const pattern_referenceOut = tr.get_mark_pattern('referenceOut');
			text = text.replace(pattern_referenceOut, "</reference>");

		// TC. [TC_00:00:25.091_TC]
			const pattern_tc = tr.get_mark_pattern('tc');
			text = text.replace(pattern_tc, `<img id="$1" src="${tag_url}$1" width="82" height="15" class="tc" data-type="tc" data-tag_id="$1" data-state="n" data-label="$2" data-data="$2">`);

		// SVG
			const pattern_svg = tr.get_mark_pattern('svg');
			text = text.replace(pattern_svg, `<img id="[$2-$3-$4-$6]" src="${tag_url}$7" height="15" class="svg" data-type="svg" data-tag_id="$4" data-state="$3" data-label="$6" data-data="$7">`);

		// DRAW
			const pattern_draw = tr.get_mark_pattern('draw');
			text = text.replace(pattern_draw, `<img id="[$2-$3-$4-$6]" src="${tag_url}[$2-$3-$4-$6]" width="38" height="15" class="draw" data-type="draw" data-tag_id="$4" data-state="$3" data-label="$6" data-data="$7">`);

		// GEO
			const pattern_geo = tr.get_mark_pattern('geo');
			text = text.replace(pattern_geo, `<img id="[$2-$3-$4-$6]" src="${tag_url}[$2-$3-$4-$6]" width="38" height="15" class="geo" data-type="geo" data-tag_id="$4" data-state="$3" data-label="$6" data-data="$7">`);

		// PAGE
			const pattern_page = tr.get_mark_pattern('page');
			text = text.replace(pattern_page, `<img id="[$2-$3-$4-$5]" src="${tag_url}[$2-$3-$4-$5]" width="38" height="15" class="page" data-type="page" data-tag_id="$4" data-state="$3" data-label="$5" data-data="$7">`);

		// PERSON
			const pattern_person = tr.get_mark_pattern('person');
			text = text.replace(pattern_person, `<img id="[$2-$3-$4-$5]" src="${tag_url}[$2-$3-$4-$5]" width="72" height="15" class="person" data-type="person" data-tag_id="$4" data-state="$3" data-label="$5" data-data="$6">`);

		// NOTE
			const pattern_note = tr.get_mark_pattern('note');
			text = text.replace(pattern_note, `<img id="[$2-$3-$4-$6]" src="${tag_url}[$2-$3-$4-$6]" width="22" height="15" class="note" data-type="note" data-tag_id="$4" data-state="$3" data-label="$6" data-data="$7">`);

		// LANG
			const pattern_lang = tr.get_mark_pattern('lang');
			text = text.replace(pattern_lang, `<img id="[$2-$3-$4-$6]" src="${tag_url}[$2-$3-$4-$6]" width="50" height="15" class="lang" data-type="lang" data-tag_id="$4" data-state="$3" data-label="$6" data-data="$7">`);

		return text
	}//end add_tag_img_on_the_fly



}//end tr



// @license-end
