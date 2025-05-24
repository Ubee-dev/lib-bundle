export const isDescendant = (child, parent) => {
    var node = child.parentNode;
    while (node != null) {
        if (node == parent) {
            return true;
        }
        node = node.parentNode;
    }
    return false;
}

export const getParent = (child, className = null) => {
    let parent = child.parentNode;
    if (!className) return parent;

    while (!parent.classList.contains(className)) {
        if(parent.nodeName === 'BODY') {
            throw new Error('Parent element with the className "' + className + '" not found')
        }

        parent = parent.parentNode;
    }

    return parent;
};

export const insertAfter = (newNode, referenceNode) => {
    referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
}