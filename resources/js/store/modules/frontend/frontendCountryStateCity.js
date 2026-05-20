import _ from "lodash";
import axios from "axios";

export const frontendCountryStateCity = {
    namespaced: true,
    state: {
        countries:[]
    },
    getters: {
     countries:function(state){
        return state.countries;
     }
    },
    actions: {
        countries: function (context, payload) {
            return new Promise((resolve, reject) => {
                axios.get(`frontend/country-state-city/countries`).then((res) => {
                    context.commit("countries", res.data.data);
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        statesByCountry: function (context, payload) {
            return new Promise((resolve, reject) => {
                axios.get(`frontend/country-state-city/states/${payload}`).then((res) => {
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        citiesByState: function (context, payload) {
            let stateName = payload;
            let countryName = "";
            if (typeof payload === 'object' && payload !== null) {
                stateName = payload.state;
                countryName = payload.country;
            }
            return new Promise((resolve, reject) => {
                axios.get(`frontend/country-state-city/cities/${stateName}`, {
                    params: { country: countryName }
                }).then((res) => {
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        }
      
    },
    mutations: {
        countries: function(state,payload){
            state.countries = payload;
        }
    },
};
