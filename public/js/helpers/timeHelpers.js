/**
 * Return decomposed time from timestamp
 * @param {number} timestamp
 * @returns {{days: int, hours: int, minutes: int, seconds: int}}
 */

export const getDecomposedTime = (timestamp) => {
    let remainingTimestamp = timestamp;
    const dayCoeff = 1000 * 60 * 60 * 24;
    const hourCoeff = 1000 * 60 * 60;
    const minuteCoeff = 1000 * 60;
    const secondCoeff = 1000;

    const days = Math.floor(remainingTimestamp/ dayCoeff);
    remainingTimestamp -= days*dayCoeff;

    const hours = Math.floor(remainingTimestamp/hourCoeff);
    remainingTimestamp -= hours*hourCoeff;

    const minutes = Math.floor(remainingTimestamp/minuteCoeff);
    remainingTimestamp -= minutes*minuteCoeff;

    const seconds = Math.floor(remainingTimestamp/secondCoeff);

    return ({days, hours, minutes, seconds})
};
