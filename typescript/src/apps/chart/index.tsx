import { mapRegister } from '@appsCollector';
import * as React from 'react';
import * as ReactDOM from 'react-dom';
import ParticipantAcquaintance from './participantAcquaintance';

export const charts = () => {
    mapRegister.register('chart.participant-acquaintance', (element, reactId, rawData, actions) => {
        const container = document.querySelector('.container');
        container.classList.remove('container');
        container.classList.add('container-fluid');
        ReactDOM.render(<ParticipantAcquaintance data={JSON.parse(rawData)}/>, element);
    });
};
