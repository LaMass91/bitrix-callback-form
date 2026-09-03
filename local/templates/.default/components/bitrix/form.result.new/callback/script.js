(function () {
	'use strict';

	var CHOICE_TYPES = ['radio', 'checkbox', 'dropdown', 'multiselect'];

	function countDigits(value) {
		var digits = value.match(/\d/g);
		return digits ? digits.length : 0;
	}

	function maskPhone(value) {
		var digits = value.replace(/\D/g, '');

		if (digits === '') {
			return '';
		}

		if (digits.charAt(0) === '8') {
			digits = '7' + digits.slice(1);
		}
		if (digits.charAt(0) !== '7') {
			digits = '7' + digits;
		}

		var rest = digits.slice(1, 11);
		var result = '+7';

		if (rest.length > 0) {
			result += ' (' + rest.slice(0, 3);
		}
		if (rest.length >= 3) {
			result += ')';
		}
		if (rest.length > 3) {
			result += ' ' + rest.slice(3, 6);
		}
		if (rest.length > 6) {
			result += '-' + rest.slice(6, 8);
		}
		if (rest.length > 8) {
			result += '-' + rest.slice(8, 10);
		}

		return result;
	}

	// чтобы маска не гнала курсор в конец
	function caretAfterDigits(value, count) {
		if (count <= 0) {
			return 0;
		}

		var seen = 0;
		for (var i = 0; i < value.length; i++) {
			if (/\d/.test(value.charAt(i))) {
				seen++;
				if (seen === count) {
					return i + 1;
				}
			}
		}

		return value.length;
	}

	var CallbackForm = {
		props: {
			schema: {
				type: Object,
				required: true
			}
		},

		data: function () {
			var values = {};

			this.schema.fields.forEach(function (field) {
				if (field.multiple) {
					values[field.sid] = field.options.filter(function (option) {
						return option.checked;
					}).map(function (option) {
						return option.value;
					});
					return;
				}

				if (field.options.length) {
					var checked = field.options.filter(function (option) {
						return option.checked;
					})[0];
					values[field.sid] = checked ? checked.value : '';
					return;
				}

				values[field.sid] = field.value || '';
			});

			return {
				visible: false,
				sending: false,
				sent: false,
				successMessage: '',
				commonError: '',
				values: values,
				errors: {},
				timer: null,
				lastFocused: null
			};
		},

		computed: {
			visibleFields: function () {
				return this.schema.fields.filter(function (field) {
					return field.type !== 'hidden';
				});
			}
		},

		mounted: function () {
			document.addEventListener('keydown', this.onKeydown);

			if (this.schema.autoOpenDelay > 0) {
				this.timer = window.setTimeout(this.show, this.schema.autoOpenDelay);
			}
		},

		beforeUnmount: function () {
			document.removeEventListener('keydown', this.onKeydown);
			window.clearTimeout(this.timer);
		},

		methods: {
			fieldId: function (field) {
				return 'cbf-' + field.sid;
			},

			isChoice: function (field) {
				return CHOICE_TYPES.indexOf(field.type) !== -1;
			},

			// у одиночной галочки подпись поля дублировала бы её текст
			showLabel: function (field) {
				if (field.type === 'radio' || field.type === 'checkbox') {
					return field.options.length > 1;
				}
				return true;
			},

			inputType: function (field) {
				if (field.type === 'email' || field.type === 'url' || field.type === 'password') {
					return field.type;
				}
				return 'text';
			},

			show: function () {
				if (this.visible) {
					return;
				}

				window.clearTimeout(this.timer);
				this.lastFocused = document.activeElement;
				this.visible = true;
				document.body.classList.add('cbf-lock');

				this.$nextTick(function () {
					var first = this.$refs.window.querySelector('input, textarea, select');
					if (first) {
						first.focus();
					}
				}.bind(this));
			},

			close: function () {
				if (!this.visible) {
					return;
				}

				this.visible = false;
				document.body.classList.remove('cbf-lock');

				if (this.lastFocused && this.lastFocused.focus) {
					this.lastFocused.focus();
				}
			},

			onKeydown: function (event) {
				if (!this.visible) {
					return;
				}

				if (event.key === 'Escape') {
					this.close();
					return;
				}

				if (event.key !== 'Tab') {
					return;
				}

				var focusable = this.$refs.window.querySelectorAll('button, input, textarea, select, a[href]');
				if (!focusable.length) {
					return;
				}

				var first = focusable[0];
				var last = focusable[focusable.length - 1];

				if (event.shiftKey && document.activeElement === first) {
					last.focus();
					event.preventDefault();
				} else if (!event.shiftKey && document.activeElement === last) {
					first.focus();
					event.preventDefault();
				}
			},

			onInput: function (field, event) {
				var value = event.target.value;

				if (field.attrs['data-mask'] === 'phone') {
					var typedDigits = countDigits(value.slice(0, event.target.selectionStart));
					value = maskPhone(value);
					event.target.value = value;

					var caret = caretAfterDigits(value, typedDigits);
					event.target.setSelectionRange(caret, caret);
				}

				this.values[field.sid] = value;
				this.clearError(field);
			},

			clearError: function (field) {
				if (this.errors[field.sid]) {
					delete this.errors[field.sid];
				}
			},

			isEmpty: function (value) {
				if (Array.isArray(value)) {
					return value.length === 0;
				}
				return String(value === null || value === undefined ? '' : value).trim() === '';
			},

			checkRule: function (rule, value) {
				var params = rule.params || {};
				var messages = this.schema.messages;

				if (rule.name === 'text_len') {
					var length = String(value).length;
					if (Number(params.LENGTH_FROM) > 0 && length < Number(params.LENGTH_FROM)) {
						return messages.minLength.replace('#LENGTH#', params.LENGTH_FROM);
					}
					if (Number(params.LENGTH_TO) > 0 && length > Number(params.LENGTH_TO)) {
						return messages.maxLength.replace('#LENGTH#', params.LENGTH_TO);
					}
				}

				if (rule.name === 'regexp' && params.PATTERN) {
					try {
						if (!new RegExp(params.PATTERN, 'u').test(String(value))) {
							return params.MESSAGE || messages.invalid;
						}
					} catch (error) {
						// не собрался в браузере — проверит сервер
					}
				}

				return '';
			},

			validate: function () {
				var errors = {};
				var messages = this.schema.messages;

				this.schema.fields.forEach(function (field) {
					var value = this.values[field.sid];

					if (this.isEmpty(value)) {
						if (field.required) {
							errors[field.sid] = field.type === 'checkbox' ? messages.requiredCheck : messages.required;
						}
						return;
					}

					if (field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
						errors[field.sid] = messages.email;
						return;
					}

					for (var i = 0; i < field.rules.length; i++) {
						var message = this.checkRule(field.rules[i], value);
						if (message) {
							errors[field.sid] = message;
							return;
						}
					}
				}.bind(this));

				this.errors = errors;

				return Object.keys(errors).length === 0;
			},

			focusFirstError: function () {
				this.$nextTick(function () {
					var invalid = this.$refs.window.querySelector('.cbf__field--invalid input, .cbf__field--invalid textarea, .cbf__field--invalid select');
					if (invalid) {
						invalid.focus();
					}
				}.bind(this));
			},

			buildBody: function () {
				var body = new FormData();

				body.append('WEB_FORM_ID', this.schema.formId);
				body.append('sessid', this.schema.sessid);
				body.append('web_form_submit', this.schema.button);

				this.schema.fields.forEach(function (field) {
					var value = this.values[field.sid];

					if (Array.isArray(value)) {
						value.forEach(function (item) {
							body.append(field.name, item);
						});
						return;
					}

					body.append(field.name, value);
				}.bind(this));

				return body;
			},

			submit: function () {
				if (this.sending) {
					return;
				}

				this.commonError = '';

				if (!this.validate()) {
					this.focusFirstError();
					return;
				}

				this.sending = true;

				fetch(this.schema.action, {
					method: 'POST',
					body: this.buildBody(),
					credentials: 'same-origin',
					headers: { 'X-Requested-With': 'XMLHttpRequest' }
				}).then(function (response) {
					return response.json();
				}).then(function (data) {
					if (data.status === 'ok') {
						this.sent = true;
						this.successMessage = data.message;
						return;
					}

					this.errors = data.fields || {};
					this.commonError = (data.common || []).join(' ');

					if (!this.commonError && !Object.keys(this.errors).length) {
						this.commonError = this.schema.messages.sendError;
					}

					this.focusFirstError();
				}.bind(this)).catch(function () {
					this.commonError = this.schema.messages.networkError;
				}.bind(this)).then(function () {
					this.sending = false;
				}.bind(this));
			}
		},

		template: [
			'<div class="cbf" v-if="visible" role="dialog" aria-modal="true" :aria-label="schema.title">',
			'  <div class="cbf__overlay" @click="close"></div>',
			'  <div class="cbf__window" ref="window">',
			'    <button type="button" class="cbf__close" :aria-label="schema.messages.close" @click="close">×</button>',

			'    <div v-if="sent" class="cbf__done">',
			'      <div class="cbf__done-icon" aria-hidden="true">✓</div>',
			'      <div class="cbf__done-text" v-html="successMessage"></div>',
			'      <button type="button" class="cbf__submit" @click="close">{{ schema.messages.close }}</button>',
			'    </div>',

			'    <form v-else class="cbf__form" novalidate @submit.prevent="submit">',
			'      <h2 class="cbf__title">{{ schema.title }}</h2>',
			'      <p class="cbf__description" v-if="schema.description" v-html="schema.description"></p>',

			'      <div v-for="field in visibleFields" :key="field.sid" class="cbf__field"',
			'           :class="{ \'cbf__field--invalid\': errors[field.sid], \'cbf__field--choice\': isChoice(field) }">',

			'        <label v-if="showLabel(field)" class="cbf__label" :for="fieldId(field)">',
			'          <span v-html="field.label"></span><span v-if="field.required" class="cbf__required">*</span>',
			'        </label>',

			'        <textarea v-if="field.type === \'textarea\'" class="cbf__control cbf__textarea"',
			'                  v-bind="field.attrs" :id="fieldId(field)" :value="values[field.sid]"',
			'                  @input="onInput(field, $event)"></textarea>',

			'        <select v-else-if="field.type === \'dropdown\' || field.type === \'multiselect\'" class="cbf__control"',
			'                v-bind="field.attrs" :id="fieldId(field)" :multiple="field.multiple"',
			'                v-model="values[field.sid]" @change="clearError(field)">',
			'          <option v-if="!field.multiple" value="">—</option>',
			'          <option v-for="option in field.options" :key="option.value" :value="option.value">{{ option.label }}</option>',
			'        </select>',

			'        <div v-else-if="field.type === \'radio\' || field.type === \'checkbox\'" class="cbf__options">',
			'          <label v-for="option in field.options" :key="option.value" class="cbf__option">',
			'            <input :type="field.type" :value="option.value" v-model="values[field.sid]"',
			'                   :id="option === field.options[0] ? fieldId(field) : null" @change="clearError(field)">',
			'            <span v-html="option.label"></span>',
			'          </label>',
			'        </div>',

			'        <input v-else class="cbf__control" v-bind="field.attrs" :id="fieldId(field)"',
			'               :type="inputType(field)" :value="values[field.sid]" @input="onInput(field, $event)">',

			'        <p class="cbf__comment" v-if="field.comment">{{ field.comment }}</p>',
			'        <p class="cbf__error" v-if="errors[field.sid]" role="alert">{{ errors[field.sid] }}</p>',
			'      </div>',

			'      <p class="cbf__error cbf__error--common" v-if="commonError" role="alert">{{ commonError }}</p>',

			'      <button type="submit" class="cbf__submit" :disabled="sending">',
			'        {{ sending ? schema.messages.sending : schema.button }}',
			'      </button>',
			'      <p class="cbf__note">{{ schema.messages.requiredHint }}</p>',
			'    </form>',
			'  </div>',
			'</div>'
		].join('\n')
	};

	function init() {
		var root = document.querySelector('[data-callback-form]');
		if (!root || !window.Vue) {
			return;
		}

		var holder = root.querySelector('[data-callback-schema]');
		if (!holder) {
			return;
		}

		var schema;
		try {
			schema = JSON.parse(holder.textContent);
		} catch (error) {
			return;
		}

		var instance = window.Vue.createApp(CallbackForm, { schema: schema }).mount(root);

		window.callbackForm = {
			open: instance.show,
			close: instance.close
		};
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
