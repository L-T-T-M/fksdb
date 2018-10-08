import * as React from 'react';
import * as ReactDOM from 'react-dom';
import { IApp } from '../../index';
import Index from './components';

export const eventAccommodation: IApp = (element: Element, module: string, component: string, mode: string, rawData: string): boolean => {
    if (module !== 'events') {
        return false;
    }
    if (component !== 'accommodation') {
        return false;
    }

    const accommodationDef = JSON.parse(element.getAttribute('data-data'));
    const container = document.createElement('div');
    element.parentElement.appendChild(container);
    if (!(element instanceof HTMLInputElement)) {
        return false;
    }

    element.style.display = 'none';
    ReactDOM.render(<Index accommodationDef={accommodationDef} input={element} mode={mode}/>, container);

    return true;
};
