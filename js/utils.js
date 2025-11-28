<<<<<<< HEAD
// utils.js
export function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

export function formatNumberWithComma(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
=======
// utils.js
export function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

export function formatNumberWithComma(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
>>>>>>> 971ab47689bd561bd08c6e4d77cea7f516414d66
}