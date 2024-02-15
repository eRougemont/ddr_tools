// this script will be applied to the selected library in Zot
const libid = ZoteroPane.getSelectedLibraryID();
// a tag to check the work done in interface
const tag = '*URL';

/**
 * For an item, get data for a task, 
 * or return null if nothing to do
 */
function taskTo(item, task) {
    // get old cote
    let cote = item.getField('callNumber');
    let urlOld = item.getField('url');
    let urlNew = urlOld.replace(
        /\/[^/]+$/g,
        '/' + cote
    );
    if (urlOld == urlNew) return null;
    task.urlOld = urlOld;
    task.urlNew = urlNew;
    return task;
}
/**
 * Apply task to item
 */
function taskDo(item, task) {
    item.setField('url', task.urlNew);
}


/* build a program of tasks */

let todo = [];
// get all items
let items = await Zotero.Items.getAll(libid, true);
// filter the concerned items and populate the todo list
for (let item of items) {
    const task = taskTo(item, {});
    if (task === null) continue;
    task.id = item.id;
    todo.push(task);
}
// show the todo list, comment this line if you want the work to be done
return todo;

/* Apply to do list */

// record done things
let done = []
// open connection
await Zotero.DB.executeTransaction(async function () {
    // loop on the to do list
    for (let task of todo) {
        let item = Zotero.Items.get(task.id);
        // do not redo if tag is already applied
        if (item.hasTag(tag)) continue;
        item.addTag(tag, 1);
        taskDo(item, task)
        done.push(item.getField('title'));
        // write to db  
        await item.save({
            skipDateModifiedUpdate: true
        });
        // comment this line if you are sure
        break;
    }
});

return done;
