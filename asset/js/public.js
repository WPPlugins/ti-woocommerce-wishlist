(function ($) {
	$.fn.tinvwl_to_wishlist = function (so) {
		var sd = {
			api_url: window.location.href,
			text_create: window.tinvwl_add_to_wishlist['text_create'],
			text_already_in: window.tinvwl_add_to_wishlist['text_already_in'],
			class: {
				dialogbox: '.tinvwl_add_to_select_wishlist',
				select: '.tinvwl_wishlist',
				newtitle: '.tinvwl_new_input',
				dialogbutton: '.tinvwl_button_add'
			},
			redirectTimer: null,
			onPrepareList: function () {},
			onGetDialogBox: function () {},
			onPrepareDialogBox: function () {
				if (!$('body > .tinv-wishlist').length) {
					$('body').append($('<div>').addClass('tinv-wishlist'));
				}
				$(this).appendTo('body > .tinv-wishlist');
			},
			onCreateWishList: function (wishlist) {
				$(this).append($('<option>').html(wishlist.title).attr('value', wishlist.ID).toggleClass('tinv_in_wishlist', wishlist.in));
			},
			onSelectWishList: function () {},
			onDialogShow: function () {
				$(this).addClass('tinv-modal-open');
			},
			onDialogHide: function () {
				$(this).removeClass('tinv-modal-open');
			},
			onInited: function () {},
			onClick: function () {
				if ( $(this).is('.disabled-add-wishlist') ) {
					return false;
				}
				if (this.tinvwl_dialog) {
					this.tinvwl_dialog.show_list.call(this);
				} else {
					s.onActionProduct.call(this);
				}
			},
			onPrepareDataAction: function () {},
			filterProductAlreadyIn: function (WList) {
				var WList = WList || [],
				data = {};
				$('form.cart[method=post], .woocommerce-variation-add-to-cart').find('input, select').each(function () {
					var name_elm = $(this).attr('name'),
					type_elm = $(this).attr('type'),
					value_elm = $(this).val();
					if ('checkbox' === type_elm || 'radio' === type_elm) {
						if ($(this).is(':checked')) {
							data['form' + name_elm] = value_elm;
						}
					} else {
						data['form' + name_elm] = value_elm;
					}
				});
				data = data['formvariation_id'];
				return WList.filter(function (wishlist) {
					if ('object' === typeof wishlist.in && 'string' === typeof data) {
						var number = parseInt(data);
						return 0 <= wishlist.in.indexOf(number);
					}
					return wishlist.in;
				});
			},
			onMultiProductAlreadyIn: function (WList) {
				var WList = WList || [];
				WList = s.onPrepareList.call(WList) || WList;
				WList = s.filterProductAlreadyIn.call(this, WList) || WList;
				$(this).parent().parent().find('.already-in').remove();
				var text = '';
				switch (WList.length) {
				case 0:
					break;
				default:
					var text = $('<ul>');
					$.each(WList, function (k, wishlist) {
						text.append($('<li>').html($('<a>').html(wishlist.title).attr({
									href: wishlist.url
								})).attr('value', wishlist.ID));
					});
					break;
				}
				if (text.length) {
					$(this).closest('.tinv-modal-inner').find('img').after($('<div>').addClass('already-in').html(s.text_already_in + ' ').append(text));
				}
			},
			onAction: {
				redirect: function (url) {
					if (s.redirectTimer) {
						clearTimeout(s.redirectTimer);
					}
					s.redirectTimer = window.setTimeout(function () {
							window.location.href = url;
						}, 4000);
					return true;
				},
				wishlists: function (wishlist) {
					$(this).attr('tinv-wl-list', wishlist);
				},
				msg: function (html) {
					if (!html) {
						return false;
					}
					var $msg = $(html).eq(0);
					if (!$('body > .tinv-wishlist').length) {
						$('body').append($('<div>').addClass('tinv-wishlist'));
					}
					$('body > .tinv-wishlist').append($msg);
					$msg.on('click', '.tinv-close-modal, .tinvwl_button_close, .tinv-overlay', function (e) {
						e.preventDefault();
						$msg.remove();
						if (s.redirectTimer) {
							clearTimeout(s.redirectTimer);
						}
					});
				},
				status: function (status) {
					if (status) {
						$(this).addClass('tinvwl-product-in-list');
					}
				}
			}
		};
		sd.onActionProduct = function (id, name) {
			var data = {
				tinv_wishlist_id: id || '',
				tinv_wishlist_name: name || '',
				product_type: $(this).attr('tinv-wl-producttype'),
				product_id: $(this).attr('tinv-wl-product') || 0,
				product_variation: $(this).attr('tinv-wl-productvariation') || 0
			},
			a = this;
			$('form.cart[method=post], .woocommerce-variation-add-to-cart').find('input, select').each(function () {
				var name_elm = $(this).attr('name'),
				type_elm = $(this).attr('type'),
				value_elm = $(this).val();
				if ('checkbox' === type_elm || 'radio' === type_elm) {
					if ($(this).is(':checked')) {
						data['form' + name_elm] = value_elm;
					}
				} else {
					data['form' + name_elm] = value_elm;
				}
			});
			data = s.onPrepareDataAction.call(a, data) || data;
			$.post(s.api_url, data, function (body) {
				s.onDialogHide.call(a.tinvwl_dialog);
				if ('object' === typeof body) {
					for (var k in body) {
						if ('function' === typeof s.onAction[k]) {
							if (s.onAction[k].call(a, body[k])) {
								return;
							}
						}
					}
				} else {
					if ('function' === typeof s.onAction['msg']) {
						s.onAction['msg'].call(a, body);
					}
				}
			});
		};
		var s = $.extend(true, {}, sd, so);
		return $(this).each(function () {
			if (!$(this).attr('tinv-wl-list')) {
				return false;
			}
			if (s.dialogbox) {
				if (s.dialogbox.length) {
					this.tinvwl_dialog = s.dialogbox;
				}
			}
			if (!this.tinvwl_dialog) {
				this.tinvwl_dialog = s.onGetDialogBox.call(this);
			}
			if (!this.tinvwl_dialog) {
				var _tinvwl_dialog = $(this).nextAll(s.class.dialogbox).eq(0);
				if (_tinvwl_dialog.length) {
					this.tinvwl_dialog = _tinvwl_dialog;
				}
			}
			if (this.tinvwl_dialog) {
				s.onPrepareDialogBox.call(this.tinvwl_dialog);
				if ('function' !== typeof this.tinvwl_dialog.update_list) {
					this.tinvwl_dialog.update_list = function (WL) {
						var $select = $(this).find(s.class.select).eq(0);
						$(this).find(s.class.newtitle).hide().val('');
						$select.html('');
						$.each(WL, function (k, v) {
							s.onCreateWishList.call($select, v);
						});
						if (s.text_create) {
							s.onCreateWishList.call($select, {
								ID: '',
								title: s.text_create,
								in: false
							});
						}
						s.onMultiProductAlreadyIn.call($select, WL);
						s.onSelectWishList.call($select, WL);
						$(this).find(s.class.newtitle).toggle('' === $select.val());
					}
				}
				if ('function' !== typeof this.tinvwl_dialog.show_list) {
					this.tinvwl_dialog.show_list = function () {
						var WList = $.parseJSON($(this).attr('tinv-wl-list')) || [];
						if (WList.length) {
							WList = s.onPrepareList.call(WList) || WList;
							this.tinvwl_dialog.update_list(WList);
							s.onDialogShow.call(this.tinvwl_dialog);
						} else {
							s.onActionProduct.call(this);
						}
					}
				}
				var a = this;
				$(this.tinvwl_dialog).find(s.class.dialogbutton).off('click').on('click', function () {
					var b = $(a.tinvwl_dialog).find(s.class.select),
					c = $(a.tinvwl_dialog).find(s.class.newtitle),
					d;
					if (b.val() || c.val()) {
						s.onActionProduct.call(a, b.val(), c.val());
					} else {
						d = c.is(':visible') ? c : b;
						d.addClass('empty-name-wishlist');
						window.setTimeout(function () {
							d.removeClass('empty-name-wishlist');
						}, 1000);
					}
				});
			}
			$(this).off('click').on('click', s.onClick);
			s.onInited.call(this, s);
		});
	};
	$(document).ready(function () {
		$('body').on('click', '.tinvwl_add_to_wishlist_button', function (e) {
            if ($(this).is('.disabled-add-wishlist')) {
                e.preventDefault();
                window.alert(tinvwl_add_to_wishlist.i18n_make_a_selection_text);
                return;
            }
            if ($(this).is('.inited-add-wishlist')) {
                return;
            }
			$(this).tinvwl_to_wishlist({
				onInited: function (s) {
                    $(this).addClass('inited-add-wishlist');
					s.onClick.call(this);
				}
			});
		});
		$('.tinvwl_move_product_button').tinvwl_to_wishlist({
			class: {
				dialogbox: '.tinvwl_wishlist_move',
				select: '.tinvwl_wishlist',
				newtitle: '.tinvwl_new_input',
				dialogbutton: '.tinvwl_button_move'
			},
			onPrepareDataAction: function (data) {
				data.tinv_from_wishlist_id = $(this).attr('tinv-wl');
				data.tinv_to_wishlist_id = data.tinv_wishlist_id;
				if (!data.tinv_wishlist_name) {
					data.tinv_wishlist_name = '-';
				}
				delete data.tinv_wishlist_id;
				return data;
			},
			onPrepareList: function () {
				return this.filter(function (a) {
					return !a.hide;
				});
			},
			onAction: {
				redirect: function (url) {
					return window.location.href = url;
				}
			}
		});
		$('.global-cb').on('click', function () {
			$(this).closest('table').eq(0).find('.product-cb input[type=checkbox], .wishlist-cb input[type=checkbox]').prop('checked', $(this).is(':checked'))
		});
	});
})(jQuery);
(function ($) {
	$.fn.tinvwl_break_submit = function (so) {
		var sd = {
			selector: 'input, select, textarea',
			ifempty: true,
			invert: false,
			validate: function () {
				return $(this).val();
			},
			rule: function () {
				var form_elements = $(this).parents('form').eq(0).find(s.selector),
				trigger = s.invert;
				if (0 === form_elements.length) {
					return s.ifempty;
				}
				form_elements.each(function () {
					if ((trigger && !s.invert) || (!trigger && s.invert)) {
						return;
					}
					trigger = Boolean(s.validate.call($(this)));
				});
				return trigger;
			}
		};
		var s = $.extend(true, {}, sd, so);
		return $(this).each(function () {
			$(this).on('click', function (event) {
				if (!s.rule.call($(this))) {
					event.preventDefault();
				}
			});
		});
	};
	$(document).ready(function () {
		$('.tinvwl-break-input').tinvwl_break_submit({
			selector: '.tinvwl-break-input-filed'
		});
		$('.tinvwl-break-checkbox').tinvwl_break_submit({
			selector: 'table td input[type=checkbox]',
			validate: function () {
				return $(this).is(':checked');
			}
		});
	});
})(jQuery);
(function ($) {
	$('.variations_form').each(function () {
		var c = $(this),
		e = c.find('.tinvwl_add_to_wishlist_button');
		c.on('hide_variation', function (a) {
			a.preventDefault();
			e.addClass('disabled-add-wishlist');
		}).on('show_variation', function (a, b, d) {
			a.preventDefault();
			e.removeClass('disabled-add-wishlist');
		});
	});
})(jQuery);
(function ($) {
	$(document).ready(function () {
		$.fn.tinvwl_modal = function (so) {
			var sd = {
				showClass: 'tinv-modal-open',
				modal: '.tinv-modal',
				onPrepare: function () {
					if (!$('body > .tinv-wishlist').length) {
						$('body').append($('<div>').addClass('tinv-wishlist'));
					}
					$(this).appendTo('body > .tinv-wishlist');
				}
			},
			s = $.extend(true, {}, sd, so);
			return $(this).each(function () {
				var a = $(this),
				b = a.next(s.modal);
				s.onPrepare.call(b);
				a.on('click', function () {
					b.addClass(s.showClass);
				});
			});
		};
		$('.tinv-modal-btn').tinvwl_modal({});
		$('#tinvwl_manage_actions, #tinvwl_product_actions').addClass('form-control').parent().wrapInner('<div class="input-group tinvwl-no-full">').find('button').wrap('<span class="input-group-btn">');
		$('.tinv-lists-nav').each(function () {
			if (!$.trim($(this).html()).length) {
				$(this).remove();
			}
		});
		$('.social-buttons .social').on('click', function (e) {
			var newWind = window.open($(this).attr('href'), $(this).attr('title'), "width=420,height=320,resizable=yes,scrollbars=yes,status=yes");
			if (newWind) {
				newWind.focus();
				e.preventDefault();
			}
		});
		$('.tinvwl-select-all').on('click', function (e) {
			e.preventDefault();
			$(this).parent().parent().find('ul li input[type="checkbox"]').attr('checked', true);
		});
		$('.tinvwl-select-none').on('click', function (e) {
			e.preventDefault();
			$(this).parent().parent().find('ul li input[type="checkbox"]').attr('checked', false);
		});
		$('body').on('click', '.tinv-wishlist .tinv-overlay, .tinv-wishlist .tinv-close-modal, .tinv-wishlist .tinvwl_button_close', function (e) {
			e.preventDefault();
			$(this).parents('.tinv-modal:first').removeClass('tinv-modal-open');
		});
		$('body').on('click', '.tinv-wishlist .tinvwl-btn-onclick', function (e) {
			var url = $(this).data('url');
			if (url) {
				e.preventDefault();
				window.location = $(this).data('url');
			}
		});
		var navigationButton = $('.tinv-wishlist .navigation-button');
		if (navigationButton.length) {
			navigationButton.each(function () {
				var navigationButtons = $(this).find('> li');
				if (navigationButtons.length < 5) {
					navigationButtons.parent().addClass('tinvwl-btns-count-' + navigationButtons.length);
				}
			});
		}
		$('.tinv-login .showlogin').unbind("click").on('click', function (e) {
			e.preventDefault();
			$(this).closest('.tinv-login').find('.login').toggle();
		});
		$('.tinv-wishlist table.tinvwl-table-manage-list tfoot td').each(function () {
			$(this).toggle(!!$(this).children().not('.look_in').length || !!$(this).children('.look_in').children().length);
		});
	})
})(jQuery);