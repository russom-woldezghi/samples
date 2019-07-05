/**
 * Created by woldezghi on 7/18/18.
 */

new Vue({
	el: "#listing",
	data: function () {
		// Note: We aren't using the API endpoint. We load directly from
		// server data in drupalSettings.russom_example_data.
		// The apiURL can still be used for testing.
		apiURL = "/api/company/russom_example_listing_endpoint.json?_format=json";
		return {
			company: drupalSettings.russom_example_data,
			sort: "result",
			search: null,
			selectLocationFilter: "All",
			selectIssueFilter: "All",
			selectSolutionFilter: "All",
			selectCommodityFilter: "All",
			totalCount: "",
			range: 11,
			showLoadMore: true,
			showResultsCount: false,
			terms: [],
		}
	},

	computed: {
		filter: function () {

			var search = this.search;
			var city = this.selectLocationFilter;
			var issues = this.selectIssueFilter;
			var solutions = this.selectSolutionFilter;
			var commodities = this.selectCommodityFilter;
			var sort = this.sort;
			var company = this.company;
			var vm = this;

			switch (sort) {
				case 'result':
					var result = company.sort(function (a, b) {
						var year_now = (new Date()).getFullYear();
						var ayears = 0;
						var byears = 0;
						if (a.year > 0) {
							ayears = parseInt(year_now) - parseInt(a.year);
						}
						if (b.year > 0) {
							byears = parseInt(year_now) - parseInt(a.year);
						}
						var aval = parseInt(ayears) + parseInt(a.number_members) + parseInt(a.badges);
						var bval = parseInt(byears) + +parseInt(b.number_members) + parseInt(b.badges);
						return vm.orderByInteger(aval, bval);
					});
					break;
				case 'title':
					var title = company.sort(function (a, b) {
						return vm.orderByString(a.title, b.title);
					});
					break;
				case 'rating':
					var rating = company.sort(function (a, b) {
						return vm.orderByInteger(a.rating, b.rating);
					});
					break;
				case 'year':
					var year = company.sort(function (a, b) {
						return vm.orderByInteger(a.year, b.year);
					});
					break;
			}

			if ((search == null)
				&& (city == "All")
				&& (issues == "All")
				&& (solutions == "All")
				&& (commodities == "All")) {
				return company;
			}
			else {
				this.showResultsCount = true;
				return company.filter(function (comp) {
					return (search == null || comp.title.toLowerCase().indexOf(search.toLowerCase()) >= 0)
						&& (city == "All" || vm.multipleValues(comp.city, city))
						&& (issues == "All" || vm.multipleValues(comp.issues, issues))
						&& (solutions == "All" || vm.multipleValues(comp.solutions, solutions))
						&& (commodities == "All" || vm.multipleValues(comp.commodities, commodities));
				});
			}
		},
		locationOptions: function () {
			var company_location = [];
			var company = this.company;
			jQuery.each(company, function (i, comp) {
				company_location.push({state: comp.state, city: comp.city});
			});
			var states = {};
			for (var i = 0; i < company_location.length; i++) {
				var stateName = company_location[i].state;
				if (!states[stateName]) {
					states[stateName] = [];
				}
				if (states[stateName].indexOf(company_location[i].city) == -1) {
					states[stateName].push(company_location[i].city);
				}
				states[stateName].sort();
			}
			company_location = [];
			for (var stateName in states) {
				jQuery.unique(states[stateName]);
			}
			return states;
		},
	},
	methods: {
		hasValue: function () {
			var val = this.$refs.input.value;
			if (val && val.length > 0) {
				this.showResultsCount = true;
			} else if (val && val.length < 0) {
				this.showResultsCount = false;
			} else {
				this.showResultsCount = false;
			}
		},
		updateResults: function () {
			this.range += 12;
			var load_more_count = this.totalCount - this.range;

			if (load_more_count <= 1) {
				this.showLoadMore = false;
			}
		},
		watchResults: function (count) {
			counter = count;
			if (counter <= 12) {
				this.showLoadMore = false;
				if (counter == 0) {
					this.showLoadMore = false;
				}
			}
		},
		orderByInteger: function (a, b) {
			return (b != null) - (a != null) || b - a;
		},
		orderByString: function (a, b) {
			a = a.toLowerCase();
			b = b.toLowerCase();
			return (a < b) ? -1 : (a > b) ? 1 : 0;
		},
		taxonomyOptions: function (company, taxonomy) {
			var terms = [];
			jQuery.each(company, function (i, comp) {
				jQuery.each(comp[taxonomy], function (i, term) {
					if (terms.indexOf(term) == -1) {
						terms.push(term);
					}
				});
			});
			terms.sort();

			return terms;
		},
		multipleValues: function (field, values) {
			if (!jQuery.isArray(field)) {
				field = [field];
			}
			return jQuery(field).not(values).length == 0 || jQuery(values).not(field).length == 0;
		},
		yearFounded: function (year) {
			if (year) {
				var year_now = (new Date()).getFullYear();
				var years_business = parseInt(year_now) - parseInt(year);
				return years_business;
			}
		},
		formReset: function (event) {
			this.search = null;
			this.selectLocationFilter = "All";
			this.selectIssueFilter = "All";
			this.selectSolutionFilter = "All";
			this.selectCommodityFilter = "All";

			jQuery("#location").val("").trigger('chosen:updated');
			jQuery("#issue").val("").trigger('chosen:updated');
			jQuery("#solution").val("").trigger('chosen:updated');
			jQuery("#commodities").val("").trigger('chosen:updated');

			this.showLoadMore = true;
			this.showResultsCount = false
		},
		imageTeaserCSS: function (image) {
			if (image.uri) {
				return "teaser__image";
			}
			else {
				return "teaser__image teaser__no-image";
			}
		},
		imageTeaserStyle: function (image) {
			if (image.uri) {
				return "backgroundImage: url(" + image.uri + ");";
			}
		},
		locationCompany: function (city, state) {
			if (state == 'ZZ') {
				return city;
			}
			else {
				return city + ', ' + state;
			}
		}
	},
});

Vue.component("chosen-select", {
	props: {
		"multiple": Boolean,
	},
	template: `<select v-once :multiple="multiple"><slot></slot></select>`,
	mounted(){
		jQuery(this.$el)
			.val(this.value)
			.chosen({width: '100%'})
			.on("change", e = > this.$emit('input', jQuery(this.$el).val())
	)
	},
	updated(){
		// console.log(jQuery(this.$el).val(this.value))
	}
});

Vue.component("star-rating", {
	props: {
		"name": String,
		"value": null,
		"disabled": Boolean
	},
	template: `<ul>\
        <li v-for="rating in ratings" \
        :class="{\'active\': ((value >= rating) && value != null), \'is-disabled\': disabled}" \
        >\
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 20">
          <path d="M10.5 16.6L17 20l-1.2-7.2L21 7.6l-7.2-1L10.5 0 7.3 6.6 0 7.6l5.3 5.2L4 20z"></path>
        </svg>
        </li></ul>`,

	data: function () {
		return {
			ratings: [1, 2, 3, 4, 5]
		};
	},
});
