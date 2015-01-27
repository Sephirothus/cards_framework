var Params = {
	imgExt: 'jpg',
	cardPath: function(id, is_small) {
		return '/imgs/cards/'+id+(is_small ? '-small' : '')+'.'+Params.imgExt;
	},
};