import { dispatchNetteFetch } from '@fetchApi/middleware/fetch';
import {
    Action,
    Dispatch,
} from 'redux';
import { Store } from '../reducers';

export const NEW_DATA_ARRIVED = 'NEW_DATA_ARRIVED';
export const newDataArrived = (data) => {
    return {
        data,
        type: NEW_DATA_ARRIVED,
    };
};

export const deleteUploadedFile = (dispatch: Dispatch<Action<string>>, accessKey: string, submitId: number, link: string) => {
    return dispatchNetteFetch<{ submitId: number }, any, Store>(accessKey, dispatch, {
        act: 'revoke',
        requestData: {
            submitId,
        },
    }, () => null, () => null, link);
};
