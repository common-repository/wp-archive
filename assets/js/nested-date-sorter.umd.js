(function (global, factory) {
	typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory() :
	typeof define === 'function' && define.amd ? define(factory) :
	  (global = global || self, global.nestedDateSorter = factory());
}(this, function () { 'use strict';

	function date_strings_to_datetimes(data) {
		return data.map(function (element) {
			element.datetime = new Date(element.date.replace(/-/g, "/"));
			return element;
		});
	}

	function grouper(data) {
		var grouped = [];
		date_strings_to_datetimes(data).map(function (element) {
			var full_year  = element.datetime.getFullYear();
			var year_index = grouped.findIndex(function (element) {
				return element.year === full_year;
			});

			if (year_index === -1) {
				grouped.push({
					year: full_year,
					months: []
				});
				year_index = grouped.findIndex(function (element) {
					return element.year === full_year;
				});
			}

			var month_number = element.datetime.getMonth();
			var month_index  = grouped[year_index].months.findIndex(function (element) {
				return element.number === month_number;
			});

			if (month_index === -1) {
				grouped[year_index].months.push({
					number: month_number,
					posts: []
				});
				month_index = grouped[year_index].months.findIndex(function (element) {
					return element.number === month_number;
				});
			}

			grouped[year_index].months[month_index].posts.push(element);
		});
		return grouped;
	}

	return grouper;

}));
